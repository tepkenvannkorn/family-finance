<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Controllers;

use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Transaction;
use App\Modules\Transactions\Policies\TransactionPolicy;
use App\Modules\Transactions\Repositories\TransactionRepository;
use App\Modules\Transactions\Requests\TransactionRequest;
use App\Modules\Transactions\Services\FileUploadService;

final class TransactionController
{
    private const PER_PAGE = 25;

    public function index(Request $request): void
    {
        [$filters, $page] = $this->parseFilters($request);
        $repo = new TransactionRepository();
        $result = $repo->filtered($filters, (string) $request->input('sort', 'transaction_date'), (string) $request->input('dir', 'desc'), $page, self::PER_PAGE);

        echo View::render('Transactions::index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'categories' => Category::active(),
            'roleName' => (string) Session::get('role_name'),
            'userId' => (int) Session::get('user_id'),
            'success' => Session::pull('success'),
            'error' => Session::pull('error'),
        ]);
    }

    /** AJAX partial used for live search/filter/sort/pagination without a full page reload (spec §8). */
    public function searchPartial(Request $request): void
    {
        [$filters, $page] = $this->parseFilters($request);
        $repo = new TransactionRepository();
        $result = $repo->filtered($filters, (string) $request->input('sort', 'transaction_date'), (string) $request->input('dir', 'desc'), $page, self::PER_PAGE);

        echo View::render('Transactions::_rows', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'roleName' => (string) Session::get('role_name'),
            'userId' => (int) Session::get('user_id'),
        ], layout: null);
    }

    public function create(Request $request): void
    {
        echo View::render('Transactions::form', [
            'transaction' => null,
            'attachments' => [],
            'categories' => Category::active(),
            'errors' => Session::pull('form_errors', []),
            'old' => Session::pull('old', []),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request): void
    {
        $errors = TransactionRequest::validate($request);

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Session::flash('old', $request->all());
            Response::redirect('/transactions/create');
        }

        $transaction = Transaction::create([
            'type' => $request->input('type'),
            'amount' => (string) $request->input('amount'),
            'currency' => $request->input('currency'),
            'category_id' => (int) $request->input('category_id'),
            'description' => $request->input('description'),
            'notes' => $request->input('notes'),
            'transaction_date' => $request->input('transaction_date'),
            'transaction_time' => $request->input('transaction_time', date('H:i:s')),
            'created_by' => (int) Session::get('user_id'),
        ]);

        $this->handleUploads($transaction->id);

        AuditLogger::log((int) Session::get('user_id'), 'transaction.create', 'transaction', $transaction->id);

        Session::flash('success', 'Transaction added.');
        Response::redirect('/transactions');
    }

    public function edit(Request $request, string $id): void
    {
        $transaction = Transaction::findById((int) $id);
        if (!$transaction) {
            Response::notFound();
        }
        if (!TransactionPolicy::canView($transaction, (int) Session::get('user_id'), (string) Session::get('role_name'))) {
            Response::forbidden();
        }

        echo View::render('Transactions::form', [
            'transaction' => $transaction,
            'attachments' => Attachment::forTransaction($transaction->id),
            'categories' => Category::active(),
            'errors' => Session::pull('form_errors', []),
            'old' => Session::pull('old', []),
            'canEdit' => TransactionPolicy::canEdit($transaction, (int) Session::get('user_id'), (string) Session::get('role_name')),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $transaction = Transaction::findById((int) $id);
        if (!$transaction) {
            Response::notFound();
        }
        if (!TransactionPolicy::canEdit($transaction, (int) Session::get('user_id'), (string) Session::get('role_name'))) {
            Response::forbidden();
        }

        $errors = TransactionRequest::validate($request);

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Session::flash('old', $request->all());
            Response::redirect("/transactions/{$id}/edit");
        }

        $transaction->update([
            'type' => $request->input('type'),
            'amount' => (string) $request->input('amount'),
            'currency' => $request->input('currency'),
            'category_id' => (int) $request->input('category_id'),
            'description' => $request->input('description'),
            'notes' => $request->input('notes'),
            'transaction_date' => $request->input('transaction_date'),
            'transaction_time' => $request->input('transaction_time', date('H:i:s')),
        ], (int) Session::get('user_id'));

        $this->handleUploads($transaction->id);

        AuditLogger::log((int) Session::get('user_id'), 'transaction.update', 'transaction', $transaction->id);

        Session::flash('success', 'Transaction updated.');
        Response::redirect('/transactions');
    }

    public function destroy(Request $request, string $id): void
    {
        $transaction = Transaction::findById((int) $id);
        if (!$transaction) {
            Response::notFound();
        }
        if (!TransactionPolicy::canDelete($transaction, (int) Session::get('user_id'), (string) Session::get('role_name'))) {
            Response::forbidden();
        }

        $transaction->softDelete();
        AuditLogger::log((int) Session::get('user_id'), 'transaction.delete', 'transaction', $transaction->id);

        Session::flash('success', 'Transaction deleted.');
        Response::redirect('/transactions');
    }

    public function deleteAttachment(Request $request, string $id, string $attachmentId): void
    {
        $transaction = Transaction::findById((int) $id);
        $attachment = Attachment::findById((int) $attachmentId);

        if (!$transaction || !$attachment || $attachment->transactionId !== $transaction->id) {
            Response::notFound();
        }
        if (!TransactionPolicy::canEdit($transaction, (int) Session::get('user_id'), (string) Session::get('role_name'))) {
            Response::forbidden();
        }

        $attachment->delete();
        AuditLogger::log((int) Session::get('user_id'), 'transaction.attachment_delete', 'transaction', $transaction->id);

        Response::redirect("/transactions/{$id}/edit");
    }

    /** Streams an attachment only to users authorized to view the parent transaction — never a public URL. */
    public function downloadAttachment(Request $request, string $id, string $attachmentId): void
    {
        $transaction = Transaction::findById((int) $id);
        $attachment = Attachment::findById((int) $attachmentId);

        if (!$transaction || !$attachment || $attachment->transactionId !== $transaction->id) {
            Response::notFound();
        }
        if (!TransactionPolicy::canView($transaction, (int) Session::get('user_id'), (string) Session::get('role_name'))) {
            Response::forbidden();
        }
        if (!is_file($attachment->filePath)) {
            Response::notFound();
        }

        header('Content-Type: ' . $attachment->mimeType);
        header('Content-Disposition: inline; filename="' . basename($attachment->originalFilename) . '"');
        header('Content-Length: ' . filesize($attachment->filePath));
        header('X-Content-Type-Options: nosniff');
        readfile($attachment->filePath);
        exit;
    }

    private function handleUploads(int $transactionId): void
    {
        if (empty($_FILES['attachment']) || ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        try {
            $uploader = new FileUploadService();
            $stored = $uploader->store($_FILES['attachment'], $transactionId);
            Attachment::create(
                $transactionId,
                $stored['path'],
                $stored['original_name'],
                $stored['mime'],
                $stored['size'],
                (int) Session::get('user_id')
            );
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
    }

    /** @return array{0: array, 1: int} */
    private function parseFilters(Request $request): array
    {
        $roleName = (string) Session::get('role_name');
        $userId = (int) Session::get('user_id');

        $filters = array_filter([
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'type' => $request->input('type'),
            'currency' => $request->input('currency'),
            'category_id' => $request->input('category_id') ? (int) $request->input('category_id') : null,
            'user_id' => $request->input('user_id') ? (int) $request->input('user_id') : null,
            'keyword' => $request->input('keyword'),
            'amount_min' => $request->input('amount_min'),
            'amount_max' => $request->input('amount_max'),
        ], fn ($v) => $v !== null && $v !== '');

        // Members can only ever see their own transactions, regardless of what's in the query string
        // (spec §3.2: members view their own dashboard/reports; only admins "view all reports").
        if ($roleName !== 'admin') {
            $filters['user_id'] = $userId;
        }

        $page = max(1, (int) $request->input('page', 1));

        return [$filters, $page];
    }
}

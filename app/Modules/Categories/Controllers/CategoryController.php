<?php

declare(strict_types=1);

namespace App\Modules\Categories\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;

final class CategoryController
{
    public function index(Request $request): void
    {
        $stmt = Database::connection()->query('SELECT * FROM categories ORDER BY type, sort_order, name');
        echo View::render('Categories::index', [
            'categories' => $stmt->fetchAll(),
            'success' => Session::pull('success'),
            'error' => Session::pull('error'),
        ]);
    }

    public function store(Request $request): void
    {
        $type = (string) $request->input('type', '');
        $name = trim((string) $request->input('name', ''));

        if (!in_array($type, ['income', 'expense'], true) || $name === '') {
            Session::flash('error', 'Please provide a valid type and name.');
            Response::redirect('/categories');
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO categories (type, name, sort_order, created_by) VALUES (:type, :name, 999, :created_by)'
        );
        try {
            $stmt->execute(['type' => $type, 'name' => $name, 'created_by' => (int) Session::get('user_id')]);
            AuditLogger::log((int) Session::get('user_id'), 'category.create', 'category', (int) Database::connection()->lastInsertId());
            Session::flash('success', 'Category added.');
        } catch (\PDOException) {
            Session::flash('error', 'That category already exists for this type.');
        }

        Response::redirect('/categories');
    }

    public function toggleActive(Request $request, string $id): void
    {
        $category = Category::findById((int) $id);
        if (!$category) {
            Response::notFound();
        }

        $stmt = Database::connection()->prepare('UPDATE categories SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $category->isActive ? 0 : 1, 'id' => $category->id]);

        AuditLogger::log((int) Session::get('user_id'), 'category.toggle_active', 'category', $category->id);
        Session::flash('success', 'Category updated.');
        Response::redirect('/categories');
    }
}

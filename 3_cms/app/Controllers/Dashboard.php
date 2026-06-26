<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\TransactionModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $productModel = new ProductModel();
        $transactionModel = new TransactionModel();

        $recentTransactions = $transactionModel
            ->select('transactions.*, users.name AS user_name, products.product_name')
            ->join('users', 'users.id = transactions.user_id')
            ->join('products', 'products.id = transactions.product_id')
            ->orderBy('transactions.id', 'DESC')
            ->limit(5)
            ->findAll();

        return view('dashboard/index', [
            'title' => 'Dashboard LamP CMS',
            'stats' => [
                'users' => $userModel->countAllResults(),
                'products' => $productModel->countAllResults(),
                'transactions' => $transactionModel->countAllResults(),
                'stock' => $productModel->selectSum('qty_in_stock')->first()['qty_in_stock'] ?? 0,
            ],
            'recentTransactions' => $recentTransactions,
        ]);
    }
}

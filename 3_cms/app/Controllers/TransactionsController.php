<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;

class TransactionsController extends BaseController
{
    private TransactionModel $transactionModel;
    private UserModel $userModel;
    private ProductModel $productModel;
    private BaseConnection $db;

    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $transactions = $this->transactionModel
            ->select('transactions.*, users.name AS user_name, products.product_name')
            ->join('users', 'users.id = transactions.user_id')
            ->join('products', 'products.id = transactions.product_id')
            ->orderBy('transactions.id', 'DESC')
            ->findAll();

        return view('transactions/index', [
            'title' => 'Data Transaksi',
            'transactions' => $transactions,
        ]);
    }

    public function create()
    {
        return view('transactions/form', [
            'title' => 'Tambah Transaksi',
            'mode' => 'create',
            'transaction' => null,
            'users' => $this->userModel->orderBy('name', 'ASC')->findAll(),
            'products' => $this->productModel->orderBy('product_name', 'ASC')->findAll(),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function store()
    {
        $payload = $this->validatedPayload();
        if ($payload === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $product = $this->productModel->find($payload['product_id']);
        if (! $product) {
            return redirect()->back()->withInput()->with('error', 'Produk tidak ditemukan.');
        }

        if ((int) $product['qty_in_stock'] < $payload['qty']) {
            return redirect()->back()->withInput()->with('error', 'Stok produk tidak mencukupi.');
        }

        $this->db->transStart();

        $this->transactionModel->insert([
            'user_id' => $payload['user_id'],
            'product_id' => $payload['product_id'],
            'payment_method' => $payload['payment_method'],
            'qty' => $payload['qty'],
            'unit_price' => $payload['unit_price'],
            'total_price' => $payload['total_price'],
            'notes' => $payload['notes'],
        ]);

        $this->productModel->update($payload['product_id'], [
            'qty_in_stock' => (int) $product['qty_in_stock'] - $payload['qty'],
        ]);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $transaction = $this->transactionModel->find($id);
        if (! $transaction) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');
        }

        return view('transactions/form', [
            'title' => 'Edit Transaksi',
            'mode' => 'edit',
            'transaction' => $transaction,
            'users' => $this->userModel->orderBy('name', 'ASC')->findAll(),
            'products' => $this->productModel->orderBy('product_name', 'ASC')->findAll(),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function update(int $id)
    {
        $oldTransaction = $this->transactionModel->find($id);
        if (! $oldTransaction) {
            return redirect()->to('/transactions')->with('error', 'Transaksi tidak ditemukan.');
        }

        $payload = $this->validatedPayload();
        if ($payload === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $oldProduct = $this->productModel->find((int) $oldTransaction['product_id']);
        $newProduct = $this->productModel->find($payload['product_id']);

        if (! $oldProduct || ! $newProduct) {
            return redirect()->back()->withInput()->with('error', 'Produk transaksi tidak valid.');
        }

        $this->db->transStart();

        $this->productModel->update((int) $oldProduct['id'], [
            'qty_in_stock' => (int) $oldProduct['qty_in_stock'] + (int) $oldTransaction['qty'],
        ]);

        $restoredNewProduct = $this->productModel->find($payload['product_id']);
        if (! $restoredNewProduct || (int) $restoredNewProduct['qty_in_stock'] < $payload['qty']) {
            $this->db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Stok produk tidak mencukupi untuk perubahan ini.');
        }

        $this->transactionModel->update($id, [
            'user_id' => $payload['user_id'],
            'product_id' => $payload['product_id'],
            'payment_method' => $payload['payment_method'],
            'qty' => $payload['qty'],
            'unit_price' => $payload['unit_price'],
            'total_price' => $payload['total_price'],
            'notes' => $payload['notes'],
        ]);

        $this->productModel->update($payload['product_id'], [
            'qty_in_stock' => (int) $restoredNewProduct['qty_in_stock'] - $payload['qty'],
        ]);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $transaction = $this->transactionModel->find($id);
        if (! $transaction) {
            return redirect()->to('/transactions')->with('error', 'Transaksi tidak ditemukan.');
        }

        $product = $this->productModel->find((int) $transaction['product_id']);
        if (! $product) {
            return redirect()->to('/transactions')->with('error', 'Produk terkait transaksi tidak ditemukan.');
        }

        $this->db->transStart();

        $this->productModel->update((int) $product['id'], [
            'qty_in_stock' => (int) $product['qty_in_stock'] + (int) $transaction['qty'],
        ]);

        $this->transactionModel->delete($id);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/transactions')->with('error', 'Gagal menghapus transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil dihapus.');
    }

    private function validatedPayload(): ?array
    {
        $rules = [
            'user_id' => 'required|is_natural_no_zero',
            'product_id' => 'required|is_natural_no_zero',
            'payment_method' => 'required|in_list[cash,transfer,qris,credit_card]',
            'qty' => 'required|is_natural_no_zero',
            'notes' => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return null;
        }

        $product = $this->productModel->find((int) $this->request->getPost('product_id'));
        if (! $product) {
            $this->validator->setError('product_id', 'Produk tidak ditemukan.');
            return null;
        }

        $user = $this->userModel->find((int) $this->request->getPost('user_id'));
        if (! $user) {
            $this->validator->setError('user_id', 'Member tidak ditemukan.');
            return null;
        }

        $qty = (int) $this->request->getPost('qty');
        $unitPrice = (float) $product['price'];

        return [
            'user_id' => (int) $this->request->getPost('user_id'),
            'product_id' => (int) $this->request->getPost('product_id'),
            'payment_method' => $this->request->getPost('payment_method'),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $qty * $unitPrice,
            'notes' => $this->request->getPost('notes'),
        ];
    }

    private function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'credit_card' => 'Kartu Kredit',
        ];
    }
}

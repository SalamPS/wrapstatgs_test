<?php

namespace App\Controllers;

use App\Models\ProductModel;

class ProductsController extends BaseController
{
    private ProductModel $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        return view('products/index', [
            'title' => 'Etalase Produk',
            'products' => $this->productModel->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('products/form', [
            'title' => 'Tambah Produk',
            'mode' => 'create',
            'product' => null,
        ]);
    }

    public function store()
    {
        $rules = [
            'product_name' => 'required|min_length[3]|max_length[120]',
            'description' => 'permit_empty|max_length[255]',
            'qty_in_stock' => 'required|is_natural',
            'price' => 'required|decimal|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->productModel->insert([
            'product_name' => $this->request->getPost('product_name'),
            'description' => $this->request->getPost('description'),
            'qty_in_stock' => (int) $this->request->getPost('qty_in_stock'),
            'price' => (float) $this->request->getPost('price'),
        ]);

        return redirect()->to('/products')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Produk tidak ditemukan.');
        }

        return view('products/form', [
            'title' => 'Edit Produk',
            'mode' => 'edit',
            'product' => $product,
        ]);
    }

    public function update(int $id)
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Produk tidak ditemukan.');
        }

        $rules = [
            'product_name' => 'required|min_length[3]|max_length[120]',
            'description' => 'permit_empty|max_length[255]',
            'qty_in_stock' => 'required|is_natural',
            'price' => 'required|decimal|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->productModel->update($id, [
            'product_name' => $this->request->getPost('product_name'),
            'description' => $this->request->getPost('description'),
            'qty_in_stock' => (int) $this->request->getPost('qty_in_stock'),
            'price' => (float) $this->request->getPost('price'),
        ]);

        return redirect()->to('/products')->with('success', 'Produk berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            return redirect()->to('/products')->with('error', 'Produk tidak ditemukan.');
        }

        $transactionCount = model('App\\Models\\TransactionModel')->where('product_id', $id)->countAllResults();
        if ($transactionCount > 0) {
            return redirect()->to('/products')->with('error', 'Produk tidak bisa dihapus karena masih dipakai dalam transaksi.');
        }

        $this->productModel->delete($id);

        return redirect()->to('/products')->with('success', 'Produk berhasil dihapus.');
    }
}

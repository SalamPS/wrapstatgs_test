<?php

namespace App\Controllers;

use App\Models\UserModel;

class UsersController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        return view('users/index', [
            'title' => 'Data Member',
            'users' => $this->userModel->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('users/form', [
            'title' => 'Tambah Member',
            'mode' => 'create',
            'user' => null,
        ]);
    }

    public function store()
    {
        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'phone' => 'required|min_length[8]|max_length[20]',
            'address' => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->insert([
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
        ]);

        return redirect()->to('/users')->with('success', 'Member berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Member tidak ditemukan.');
        }

        return view('users/form', [
            'title' => 'Edit Member',
            'mode' => 'edit',
            'user' => $user,
        ]);
    }

    public function update(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Member tidak ditemukan.');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email,id,' . $id . ']',
            'phone' => 'required|min_length[8]|max_length[20]',
            'address' => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->update($id, [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
        ]);

        return redirect()->to('/users')->with('success', 'Member berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/users')->with('error', 'Member tidak ditemukan.');
        }

        $transactionCount = model('App\\Models\\TransactionModel')->where('user_id', $id)->countAllResults();
        if ($transactionCount > 0) {
            return redirect()->to('/users')->with('error', 'Member tidak bisa dihapus karena masih memiliki transaksi.');
        }

        $this->userModel->delete($id);

        return redirect()->to('/users')->with('success', 'Member berhasil dihapus.');
    }
}

<?php
// Autoload TCPDF (assuming you've installed it via Composer)
require_once 'vendor/autoload.php';

use TCPDF as TCPDF;

// UserModel
class UserModel {
    private $users = [];

    public function generateRandomUsers() {
        $this->users = [];
        for ($i = 0; $i < 10; $i++) {
            $this->users[] = [
                'id' => $i + 1,
                'email' => uniqid() . '@example.com',
                'username' => 'user_' . uniqid(),
                'password' => bin2hex(random_bytes(8))
            ];
        }
        return $this->users;
    }

    public function deleteUser($id) {
        $this->users = array_filter($this->users, function($user) use ($id) {
            return $user['id'] != $id;
        });
    }

    public function updateUser($id, $data) {
        foreach ($this->users as &$user) {
            if ($user['id'] == $id) {
                $user = array_merge($user, $data);
                break;
            }
        }
    }
    public function exportToPDF() {
        // Generate users if not already generated
        if (empty($this->users)) {
            $this->generateRandomUsers();
        }
    
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Application');
        $pdf->SetTitle('User Data');
        $pdf->SetSubject('User Information');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'User Data', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(80, 7, 'Email', 1);
        $pdf->Cell(60, 7, 'Username', 1);
        $pdf->Cell(50, 7, 'Password', 1);
        $pdf->Ln();
        
        // Table data
        $pdf->SetFont('helvetica', '', 10);
        foreach ($this->users as $user) {
            $pdf->Cell(80, 6, $user['email'], 1);
            $pdf->Cell(60, 6, $user['username'], 1);
            $pdf->Cell(50, 6, $user['password'], 1);
            $pdf->Ln();
        }
        
        // Output the PDF
        $pdf->Output('user_data.pdf', 'I');
        exit;
    }
}

// UserController
class UserController {
    private $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'generateUsers':
                $users = $this->model->generateRandomUsers();
                echo json_encode($users);
                exit;
            case 'deleteUser':
                $id = $_GET['id'] ?? 0;
                $this->model->deleteUser($id);
                exit;
            case 'updateUser':
                $id = $_GET['id'] ?? 0;
                $username = $_POST['username'] ?? '';
                $this->model->updateUser($id, ['username' => $username]);
                exit;
            case 'exportPDF':
                $this->model->exportToPDF();
                exit;
        }
    }
}

// Handle API requests
$controller = new UserController();
$controller->handleRequest();

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { margin: 5px; padding: 5px 10px; }
        #generatePdf { margin-top: 20px; }
    </style>
</head>
<body>
    <div id="app">
        <h1>User Management</h1>
        <table id="userTable">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <button id="generatePdf">Generate PDF</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initTable();
            document.getElementById('generatePdf').addEventListener('click', generatePDF);
        });

        function initTable() {
            fetch('index.php?action=generateUsers')
                .then(response => response.json())
                .then(users => {
                    const tbody = document.querySelector('#userTable tbody');
                    tbody.innerHTML = '';
                    users.forEach(user => {
                        const row = `
                            <tr>
                                <td>${user.email}</td>
                                <td>${user.username}</td>
                                <td>${user.password}</td>
                                <td>
                                    <button onclick="modifyUser(${user.id})">Modify</button>
                                    <button onclick="deleteUser(${user.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                });
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch(`index.php?action=deleteUser&id=${id}`, { method: 'POST' })
                    .then(() => initTable());
            }
        }

        function modifyUser(id) {
            const newUsername = prompt('Enter new username:');
            if (newUsername) {
                fetch(`index.php?action=updateUser&id=${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `username=${encodeURIComponent(newUsername)}`
                }).then(() => initTable());
            }
        }

        function generatePDF() {
            window.open('index.php?action=exportPDF', '_blank');
        }
    </script>
</body>
</html>
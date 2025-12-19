<?php
$file = "users.json";
$name = $email = $password = $confirm_password = "";
$nameErr = $emailErr = $passwordErr = $confirmPasswordErr = "";
$success = "";
$fileError = "";

if (!file_exists($file)) {
    if (file_put_contents($file, json_encode([])) === false) {
        $fileError = "Error creating users file. Please check permissions.";
    }
}

if (!is_readable($file) || !is_writable($file)) {
    if (!chmod($file, 0666)) {
        $fileError = "Error setting file permissions for users.json.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($fileError)) {

    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = htmlspecialchars(trim($_POST["name"]));
    }

    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = htmlspecialchars(trim($_POST["email"]));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = $_POST["password"];
        if (strlen($password) < 8) {
            $passwordErr = "Password must be at least 8 characters long";
        } elseif (!preg_match("/[@$!%*#?&]/", $password)) {
            $passwordErr = "Password must contain at least one special character";
        }
    }

    if (empty($_POST["confirm_password"])) {
        $confirmPasswordErr = "Confirm password is required";
    } else {
        $confirm_password = $_POST["confirm_password"];
        if ($password !== $confirm_password) {
            $confirmPasswordErr = "Passwords do not match";
        }
    }

    if (empty($nameErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr)) {

        $jsonData = @file_get_contents($file);
        if ($jsonData === false) {
            $fileError = "Error reading users file.";
        } else {
            $users = json_decode($jsonData, true);
            if (!is_array($users)) $users = [];

            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    $emailErr = "Email is already registered";
                    break;
                }
            }

            if (empty($emailErr)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $users[] = [
                    "name" => $name,
                    "email" => $email,
                    "password" => $hashedPassword
                ];

                if (@file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT)) === false) {
                    $fileError = "Error saving user data.";
                } else {
                    $success = "Registration successful!";
                    $name = $email = $password = $confirm_password = "";
                }
            }
        }
    }
}

/* Automated Tests */
$testResults = [];
$tests = [
    ["", "test1@example.com", "Password@1", "Password@1", "Name is required"],
    ["Alice", "invalidemail", "Password@1", "Password@1", "Invalid email format"],
    ["Bob", "bob@example.com", "short", "short", "Password must be at least 8 characters long"],
    ["Charlie", "charlie@example.com", "Password1", "Password1", "Password must contain at least one special character"],
    ["David", "david@example.com", "Password@1", "Password@2", "Passwords do not match"],
    ["Eve", "eve@example.com", "Password@1", "Password@1", ""],
];

foreach ($tests as $i => $t) {
    $errors = [];
    if (!$t[0]) $errors[] = "Name is required";
    if (!$t[1] || !filter_var($t[1], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!$t[2] || strlen($t[2]) < 8) $errors[] = "Password must be at least 8 characters long";
    elseif (!preg_match("/[@$!%*#?&]/", $t[2])) $errors[] = "Password must contain at least one special character";
    if ($t[2] !== $t[3]) $errors[] = "Passwords do not match";

    $testResults[] = [
        "test" => $i + 1,
        "expected" => $t[4] ?: "Success",
        "actual" => $errors ? implode(", ", $errors) : "Success"
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Registration System</title>

<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    margin: 0;
    padding: 0;
}

.container {
    max-width: 450px;
    background: #fff;
    margin: 60px auto;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

h2 {
    text-align: center;
    color: #333;
}

label {
    font-weight: bold;
}

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

input[type="submit"] {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    background: #667eea;
    border: none;
    color: white;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
}

input[type="submit"]:hover {
    background: #5a67d8;
}

.error {
    color: #e53e3e;
    font-size: 13px;
}

.success {
    background: #c6f6d5;
    color: #22543d;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
}

.file-error {
    background: #fed7d7;
    color: #742a2a;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
}

.table-container {
    max-width: 700px;
    background: #fff;
    margin: 40px auto;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #667eea;
    color: white;
    padding: 10px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

tr:nth-child(even) {
    background: #f7fafc;
}
</style>
</head>

<body>

<div class="container">
<h2>User Registration Form</h2>

<?php if ($fileError): ?>
    <div class="file-error"><?= $fileError ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success"><?= $success ?></div>
<?php endif; ?>

<form method="post">
    <label>Name</label>
    <input type="text" name="name" value="<?= $name ?>">
    <span class="error"><?= $nameErr ?></span>

    <label>Email</label>
    <input type="text" name="email" value="<?= $email ?>">
    <span class="error"><?= $emailErr ?></span>

    <label>Password</label>
    <input type="password" name="password">
    <span class="error"><?= $passwordErr ?></span>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password">
    <span class="error"><?= $confirmPasswordErr ?></span>

    <input type="submit" value="Register">
</form>
</div>

<div class="table-container">
<h2>Automated Test Results</h2>

<table>
<tr>
    <th>Test #</th>
    <th>Expected</th>
    <th>Actual</th>
</tr>

<?php foreach ($testResults as $r): ?>
<tr>
    <td><?= $r["test"] ?></td>
    <td><?= $r["expected"] ?></td>
    <td><?= $r["actual"] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</body>
</html>

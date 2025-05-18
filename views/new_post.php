<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/head.php';
include '../includes/header.php';
require_once '../includes/navbar.php';
$customHeadStyle = <<<CSS

  .submit-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 40px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 20px;
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.1);
            color: white;
        }

        .submit-container h2 {
            font-size: 2em;
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 15px;
        }

        .submit-container input[type="text"],
        .submit-container textarea {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            color: #fff;
            width: 100%;
            font-size: 1em;
            transition: border 0.2s ease;
        }

        .submit-container input:focus,
        .submit-container textarea:focus {
            border-color: #00ffc3;
            outline: none;
        }

        .submit-container button {
            background: linear-gradient(145deg, #00ffc3, #0066ff);
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1em;
            color: #000;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .submit-container button:hover {
            background: linear-gradient(145deg, #00cc9f, #0057d9);
        }

        .confirmation {
            text-align: center;
            margin-top: 20px;
            background: rgba(0, 255, 150, 0.1);
            border-left: 5px solid #00ffc3;
            padding: 10px;
            border-radius: 8px;
            font-size: 1em;
            color: #00ffc3;
        }
.submit-container {
    max-width: 800px;
    margin: 80px auto;
    padding: 40px;
    background: rgba(10, 10, 10, 0.8);
    border-radius: 20px;
    box-shadow: 0 0 35px rgba(0, 255, 255, 0.05);
    color: white;
    animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.submit-container h2 {
    font-size: 2em;
    text-align: center;
    margin-bottom: 30px;
    color: #00ffc3;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 15px;
}

.submit-container input[type="text"],
.submit-container textarea {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;
    color: #fff;
    width: 100%;
    font-size: 1em;
    transition: border 0.2s ease;
}

.submit-container input::placeholder,
.submit-container textarea::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.submit-container input:focus,
.submit-container textarea:focus {
    border-color: #00ffc3;
    outline: none;
}

.submit-container button {
    background: linear-gradient(135deg, #00ffc3, #0066ff);
    border: none;
    padding: 15px;
    border-radius: 12px;
    font-size: 1.1em;
    color: black;
    cursor: pointer;
    font-weight: bold;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
}

.submit-container button:hover {
    transform: scale(1.02);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
}

.confirmation {
    text-align: center;
    margin-top: 20px;
    background: rgba(0, 255, 150, 0.1);
    border-left: 5px solid #00ffc3;
    padding: 10px;
    border-radius: 8px;
    font-size: 1em;
    color: #00ffc3;
}
CSS;


    
?>


<div id="page-transition"></div>

<div class="submit-container">
    <h2>📝 Soumettre un nouveau sujet</h2>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $title = htmlspecialchars($_POST["title"]);
        $content = htmlspecialchars($_POST["content"]);
        $author = $_SESSION["user"]["username"];

        $stmt = $pdo->prepare("INSERT INTO posts (title, content, author, is_approved, created_at)
                               VALUES (?, ?, ?, 0, NOW())");
        $stmt->execute([$title, $content, $author]);

        echo "<div class='confirmation'>✅ Sujet soumis pour validation. Il sera visible une fois accepté par un admin.</div>";
    }
    ?>

    <form method="post">
        <input type="text" name="title" placeholder="Titre du sujet" required>
        <textarea name="content" placeholder="Contenu..." rows="6" required></textarea>
        <button type="submit">Soumettre le sujet</button>
    </form>
</div>

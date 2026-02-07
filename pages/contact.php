<?php
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact – CyberAware</title>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<style>
		:root {
			--primary: #FF8C42;
			--primary-rgb: 255,140,66;
			--platinum: #E5E4E2;
			--ink: #1f2937;
			--muted: #6b7280;
			--white: #ffffff;
			--shadow-sm: 0 10px 24px rgba(15, 23, 42, 0.08);
			--shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
		}

		* { margin: 0; padding: 0; box-sizing: border-box; }

		body {
			font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			background: radial-gradient(1000px 520px at 12% -10%, #fff1e4 0%, transparent 60%),
				linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
			color: var(--ink);
		}

		h1, h2, h3 { font-family: 'Space Grotesk', 'Segoe UI', sans-serif; }

		.header {
			background: rgba(255, 255, 255, 0.96);
			border-bottom: 1px solid rgba(255, 140, 66, 0.2);
			box-shadow: var(--shadow-sm);
		}

		.header-content {
			max-width: 1200px;
			margin: 0 auto;
			padding: 18px 28px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.logo {
			text-decoration: none;
			font-weight: 800;
			color: var(--primary);
			font-size: 22px;
			display: flex;
			gap: 10px;
			align-items: center;
		}

		.container {
			max-width: 1100px;
			margin: 0 auto;
			padding: 50px 24px 80px;
		}

		.hero {
			background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
			border-radius: 26px;
			padding: 50px 40px;
			box-shadow: var(--shadow-md);
			margin-bottom: 30px;
		}

		.contact-grid {
			display: grid;
			gap: 24px;
			grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
		}

		.contact-card {
			background: var(--white);
			border-radius: 18px;
			padding: 22px;
			border: 1px solid rgba(255, 140, 66, 0.16);
			box-shadow: var(--shadow-sm);
		}

		.contact-card h3 { margin-bottom: 10px; }

		.contact-form {
			display: grid;
			gap: 12px;
		}

		.contact-form input,
		.contact-form textarea {
			width: 100%;
			padding: 12px 14px;
			border-radius: 12px;
			border: 1px solid rgba(15, 23, 42, 0.1);
			font-family: inherit;
		}

		.contact-form button {
			padding: 12px 20px;
			border-radius: 999px;
			border: none;
			background: var(--primary);
			color: var(--white);
			font-weight: 700;
			cursor: pointer;
		}
	</style>
</head>
<body>
	<header class="header">
		<div class="header-content">
			<a class="logo" href="../index.php"><i class="fas fa-shield-alt"></i> CyberAware</a>
		</div>
	</header>

	<main class="container">
		<section class="hero">
			<h1>Let’s connect</h1>
			<p style="color: var(--muted);">Send a quick note and we will respond with next steps.</p>
		</section>

		<section class="contact-grid">
			<div class="contact-card">
				<h3>Direct lines</h3>
				<p><strong>Email:</strong> hello@cyberaware.com</p>
				<p><strong>Phone:</strong> +264 61 123 4567</p>
				<p><strong>Office:</strong> Windhoek, Namibia</p>
			</div>
			<div class="contact-card">
				<h3>Request a demo</h3>
				<form class="contact-form" action="../contact.php" method="post">
					<input type="text" name="name" placeholder="Full name" required>
					<input type="email" name="email" placeholder="Work email" required>
					<input type="text" name="company" placeholder="Company" required>
					<textarea name="message" rows="4" placeholder="Tell us what you need" required></textarea>
					<button type="submit">Send request</button>
				</form>
			</div>
		</section>
	</main>
</body>
</html>

<?php
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Compliance – CyberAware</title>
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
			background: radial-gradient(1000px 520px at 10% -10%, #fff1e4 0%, transparent 60%),
				linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
			color: var(--ink);
		}

		h1, h2, h3 { font-family: 'Space Grotesk', 'Segoe UI', sans-serif; }

		.header {
			background: rgba(255, 255, 255, 0.96);
			border-bottom: 1px solid rgba(255, 140, 66, 0.2);
			box-shadow: var(--shadow-sm);
			position: sticky;
			top: 0;
			z-index: 50;
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

		.nav-menu {
			display: flex;
			gap: 18px;
			align-items: center;
		}

		.nav-menu a {
			text-decoration: none;
			color: var(--ink);
			font-weight: 600;
			font-size: 14px;
		}

		.container {
			max-width: 1100px;
			margin: 0 auto;
			padding: 40px 24px 80px;
		}

		.hero {
			background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
			border-radius: 26px;
			padding: 60px 40px;
			box-shadow: var(--shadow-md);
			color: var(--ink);
			margin-bottom: 40px;
		}

		.hero p { margin-top: 10px; color: var(--muted); }

		.rail {
			display: grid;
			gap: 24px;
			grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		}

		.rail-card {
			background: var(--white);
			border-radius: 18px;
			padding: 22px;
			border: 1px solid rgba(255, 140, 66, 0.18);
			box-shadow: var(--shadow-sm);
		}

		.rail-card h3 { margin-bottom: 8px; }

		.audit-stack {
			margin-top: 36px;
			display: grid;
			gap: 18px;
		}

		.audit-item {
			background: var(--white);
			border-radius: 16px;
			padding: 18px 20px;
			border: 1px solid rgba(15, 23, 42, 0.08);
			display: flex;
			gap: 16px;
			align-items: center;
		}

		.audit-dot {
			width: 44px;
			height: 44px;
			border-radius: 12px;
			background: rgba(255, 140, 66, 0.18);
			display: grid;
			place-items: center;
			color: var(--primary);
		}

		.cta {
			margin-top: 40px;
			padding: 30px;
			border-radius: 20px;
			background: var(--white);
			border: 1px solid rgba(255, 140, 66, 0.2);
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
		}

		.btn {
			padding: 12px 20px;
			border-radius: 999px;
			background: var(--primary);
			color: var(--white);
			text-decoration: none;
			font-weight: 700;
		}
	</style>
</head>
<body>
	<header class="header">
		<div class="header-content">
			<a class="logo" href="../index.php"><i class="fas fa-shield-alt"></i> CyberAware</a>
			<nav class="nav-menu">
				<a href="../index.php#features">Features</a>
				<a href="../index.php#about">About</a>
				<a href="../index.php#compliance">Compliance</a>
				<a href="../index.php#contact">Contact</a>
			</nav>
		</div>
	</header>

	<main class="container">
		<section class="hero">
			<h1>Compliance, clarified</h1>
			<p>Translate security training into clear frameworks and audit-ready proof.</p>
		</section>

		<section class="rail">
			<div class="rail-card">
				<h3>ISO 27001</h3>
				<p>Training evidence mapped to control requirements.</p>
			</div>
			<div class="rail-card">
				<h3>NIST CSF</h3>
				<p>Guided outcomes across identify, protect, detect, respond.</p>
			</div>
			<div class="rail-card">
				<h3>GDPR + POPIA</h3>
				<p>Privacy-ready learning with full participation logs.</p>
			</div>
		</section>

		<section class="audit-stack">
			<div class="audit-item">
				<div class="audit-dot"><i class="fas fa-check"></i></div>
				<div>
					<strong>Completion Trails</strong>
					<p>Every module completion is time-stamped and exportable.</p>
				</div>
			</div>
			<div class="audit-item">
				<div class="audit-dot"><i class="fas fa-chart-line"></i></div>
				<div>
					<strong>Risk Trends</strong>
					<p>Track improvements and flag risk drops across departments.</p>
				</div>
			</div>
			<div class="audit-item">
				<div class="audit-dot"><i class="fas fa-file-export"></i></div>
				<div>
					<strong>Export Packs</strong>
					<p>PDF and CSV summaries ready for audits and leadership.</p>
				</div>
			</div>
		</section>

		<section class="cta">
			<div>
				<h3>Need a compliance walkthrough?</h3>
				<p style="color: var(--muted);">We can map your requirements to training plans.</p>
			</div>
			<a class="btn" href="../contact.php">Talk to us</a>
		</section>
	</main>
</body>
</html>

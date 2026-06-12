<?php
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>How It Works – CyberAware</title>
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
			background: radial-gradient(1000px 520px at 20% -10%, #fff1e4 0%, transparent 60%),
				linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
			color: var(--ink);
		}

		h1, h2, h3 { font-family: 'Space Grotesk', 'Segoe UI', sans-serif; }

		.container {
			max-width: 1100px;
			margin: 0 auto;
			padding: 50px 24px 80px;
		}

		.hero {
			background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
			border-radius: 26px;
			padding: 60px 40px;
			box-shadow: var(--shadow-md);
			margin-bottom: 40px;
		}

		.steps {
			display: grid;
			gap: 18px;
		}

		.step {
			background: var(--white);
			border-radius: 18px;
			padding: 22px;
			border: 1px solid rgba(255, 140, 66, 0.16);
			display: flex;
			gap: 16px;
			align-items: flex-start;
			box-shadow: var(--shadow-sm);
		}

		.step-badge {
			width: 44px;
			height: 44px;
			border-radius: 12px;
			background: rgba(255, 140, 66, 0.18);
			display: grid;
			place-items: center;
			color: var(--primary);
			font-weight: 800;
		}
	</style>
</head>
<body>
	<main class="container">
		<section class="hero">
			<h1>How CyberAware works</h1>
			<p style="color: var(--muted);">A simple cycle: learn, simulate, measure, and improve.</p>
		</section>

		<section class="steps">
			<div class="step">
				<div class="step-badge">1</div>
				<div>
					<h3>Launch training</h3>
					<p>Assign modules by department, role, or risk level.</p>
				</div>
			</div>
			<div class="step">
				<div class="step-badge">2</div>
				<div>
					<h3>Run simulations</h3>
					<p>Practice realistic phishing and social engineering scenarios.</p>
				</div>
			</div>
			<div class="step">
				<div class="step-badge">3</div>
				<div>
					<h3>Capture insights</h3>
					<p>Track completion, scores, and improvement over time.</p>
				</div>
			</div>
			<div class="step">
				<div class="step-badge">4</div>
				<div>
					<h3>Prove compliance</h3>
					<p>Export audit-ready reports with clear evidence.</p>
				</div>
			</div>
		</section>
	</main>
</body>
</html>

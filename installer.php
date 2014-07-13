<!doctype html>
<html>
	<head>
		<meta charset="utf-8" />
		<link rel="stylesheet" href="assets/css/semantic.min.css" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans Pro:200italic,200,300italic,300,400italic,400,600italic,600,700italic,700,900italic,900" />
		<style>body {font-family: "Source Sans Pro";}</style>
		<title>Installation</title>
	</head>
	<body>
		<h1 class="ui center aligned icon header">
	        <i class="circular emphasized icon settings"></i>
	        <div class="content">Installation</div>
	        <div class="sub header">Courage, vous y êtes presque !</div>
		</h1>
		<div class="ui horizontal divider">
			<i class="icon circular settings"></i>
		</div>
		<?php
		$step = (isset($_GET['step'])) ? $_GET['step'] : 0;
		switch($step) {
			case 0:
			?>
			<div class="ui steps">
				<div class="ui active step">
					Configuration du chemin d'accès au serveur
				</div>
				<div class="ui disabled step">
					Création de l'utilisateur
				</div>
				<div class="ui disabled step">
				Finalisation
				</div>
			</div>
			<form action="?step=1" method="post" class="ui form">
				<div class="field">
					<label>
					Chemin d'accès au serveur
					</label>
					<div class="ui input">
						<input type="text" name="MC_PATH" value="/var/minecraft" required />
					</div>
				</div>
				<div class="field">
					<label>
						Nom de l'éxécutable
					</label>
					<div class="ui input">
						<input type="text" name="JAR_NAME" value="craftbukkit.jar" required />
					</div>
				</div>
				<div class="field">
					<label>
						Options Java
					</label>
					<div class="ui input">
						<input type="text" name="JAVA_OPTS" value="-Xmx1G -XX:MaxPermSize=128M" />
					</div>
				</div>
				<input type="submit" class="ui teal button" />
			</form>
			<?php
			break;
			case 1:
				$configfile = "<?php\n";
				$values = $_POST;
				$values['SALT'] = base64_encode(md5(uniqid()));
				$values['MC_PATH'] .= '/';
				foreach($values as $k => $v) {
					$configfile .= "define('" . addslashes($k) . "', '" . addslashes($v) . "');\n";
				}
				file_put_contents('userconfig.php', $configfile);
				?>
				<div class="ui steps">
					<div class="ui step">
						Configuration du chemin d'accès au serveur
					</div>
					<div class="ui active step">
						Création de l'utilisateur
					</div>
					<div class="ui disabled step">
						Finalisation
					</div>
				</div>
				<div class="ui blue message">
					Veuillez maintenant définir un utilisateur, cet utilisateur sera le votre et permettra d'accéder au panel.
				</div>
				<form method="post" action="?step=2" class="ui form">
					<div class="field">
						<label for="uname">
							Nom d'utilisateur
						</label>
						<div class="ui left labeled icon input">
							<input type="text" placeholder="Nom d'utilisateur" name="username" id="uname" />
							<i class="user icon"></i>
							<div class="ui corner label">
								<i class="asterisk icon"></i>
							</div>
						</div>
					</div>
					<div class="field">
						<label for="pwd">
							Mot de passe
						</label>
						<div class="ui left labeled icon input">
							<input type="password" id="pwd" name="password" />
							<i class="lock icon"></i>
							<div class="ui corner label">
								<i class="asterisk icon"></i>
							</div>
						</div>
					</div>
					<input type="submit" class="ui teal button" value="Créér l'utilisateur" />
				</form>
				<?php
			break;
			case 2:
				require_once 'userconfig.php';
				$users = [['password' => hash('sha512', $_POST['password'] . SALT), 'username' => $_POST['username']]];
				file_put_contents("users.json", json_encode($users, JSON_PRETTY_PRINT));
				?>
				<div class="ui steps">
					<div class="ui step">
						Configuration du chemin d'accès au serveur
					</div>
					<div class="ui step">
						Création de l'utilisateur
					</div>
					<div class="ui active step">
						Finalisation
					</div>
				</div>
				<br />
				L'installation est désormais terminée, cliquez sur le bouton ci-dessous afin de supprimer l'installateur.<br />
				<a href="?step=42" class="ui teal button">Supprimer l'installateur</a>
				<?php
			break;
			case 42:
				unlink('installer.php');
				?>
				Redirection vers le panel ...
<meta http-equiv="refresh" content="1; URL=." />
				<?php
			break;
			default;
			echo "Erreur";
			break;
		}
		?>
	</body>
</html>
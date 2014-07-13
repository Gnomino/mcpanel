<?php
 session_start();
 require_once 'config.php';
 require_once 'vendor/autoload.php';
 require_once 'MinecraftQuery.php';
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
));
$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);
$view->getEnvironment()->addGlobal('name', SERVER_NAME);
function hash_pwd($pwd) {
	return hash("sha512", $pwd . SALT);
}
$user = null;
$username = $app->getCookie('username');
$password = $app->getCookie('password');
if (!empty($username) && !empty($password)) {
	$users = json_decode(file_get_contents("users.json"), true);
	foreach($users as $u) {
		if ($u['username'] == $username && $u['password'] == $password) {
			$user = $u;
			$view->getEnvironment()->addGlobal('user', $user);
		}
	}
}
$app->hook('slim.before', function () use($app, $user) {
	if (empty($user)) {
		if ($app->request->getRootUri() . $app->request->getResourceUri() != $app->urlFor('login')) {
			$app->redirect($app->urlFor('login'));
		}
	}
});
$app->get('/login', function() use($app) {
	echo $app->render("login.html");
})->name('login');
$app->post('/login', function() use($app) {
	$pwd = hash_pwd($app->request->post('password'));
	$logged = false;
	$users = json_decode(file_get_contents("users.json"), true);
	foreach($users as $u) {
		if ($u['username'] == $app->request->post('username') && $u['password'] == $pwd) {
			$app->setCookie('username', $u['username'], '1 day');
			$app->setCookie('password', $u['password'], '1 day');
			$logged = true;
		}
	}
	if ($logged) {
		$app->redirect($app->urlFor('index'));
	}
	else {
		$app->redirect($app->urlFor('login'));
	}
});
/////////////////////////// HERE COMES FUN ///////////////////////
function run_cmd($cmd) {
	exec('screen -x ' . SCREEN_NAME . ' -X eval "stuff \'' . str_replace(['"', "'"],['\\"',"'\\\"'\\\"'"], $cmd) . '\n\'"');
}
$app->get('/', function() use($app) {
	$q = new MinecraftQuery();
	$offline = false;
	try { 
		$q->connect(QUERY_HOST, QUERY_PORT);
	}
	catch(MinecraftQueryException $e) {
		$offline = true;
	}
	echo json_encode($q->getInfo(), JSON_PRETTY_PRINT);
	$booting = file_exists('.booting');
	if (!$offline && $booting) {
		unlink('.booting');
	}
	echo $app->render('index.html', ['q' => $q, 'offline' => $offline, 'booting' => $booting]);
})->name('index');

$app->get('/console', function () use($app) {
	$f = file(LOG_FILE);
	$log = array();
	for ($i = count($f)-1; $i > count($f) - 50; $i--) {
		$log[] = $f[$i];
	}
	echo $app->render('console.html', ['log' => $log]);
})->name('console');
$app->post('/commande', function () use($app) {
	run_cmd($app->request->post('cmd'));
	$app->redirect($app->urlFor('console'));
})->name('run-cmd');
$app->get('/log', function () {
$f = file(LOG_FILE);
	$log = array();
	for ($i = count($f)-1; $i > count($f) - 50; $i--) {
		$log[] = $f[$i];
	}
	echo json_encode($log);
})->name('ajax-log');
$app->get('/demarrer', function () use ($app) {
	exec('cd ' . escapeshellarg(MC_PATH) . ' && screen -dmS ' . escapeshellarg(SCREEN_NAME) . ' java ' . JAVA_OPTS . ' -jar ' . escapeshellarg(JAR_FILE));
	file_put_contents('.booting', '');
	$app->redirect($app->urlFor('index'));
})->name('power-on');
$app->get('/est-en-ligne', function () use($app) {
try { 
		$q->connect(QUERY_HOST, QUERY_PORT);
	}
	catch(MinecraftQueryException $e) {
		$offline = true;
	}
	if (empty($offline)) {
		echo 'yes';
	}
	else {
		echo 'no';
	}
})->name('is-up');

$app->get('/joueurs', function () use($app) {
	$rawops = json_decode(file_get_contents(OP_FILE), true);
	$ops = [];
	foreach ($rawops as $op) {
		$ops[] = $op['name'];
	}
	try { 
		$q = new MinecraftQuery();
		$q->connect(QUERY_HOST, QUERY_PORT);
	}
	catch(MinecraftQueryException $e) {
		return $app->redirect($app->urlFor('index'));
	}
	$online = $q->getPlayers();
	echo $app->render('players.html', ['online' => $online, 'ops' => $ops]);
})->name('players');
$app->get('/joueurs/ops', function() use($app) {
	$ops = json_decode(file_get_contents(OP_FILE), true);
	echo $app->render('ops.html', ['ops' => $ops]);
})->name('players-ops');
$app->get('/joueurs/bannis', function () use($app) {
	$bannedp = json_decode(file_get_contents(BANNED_PLAYERS_FILE), true);
	echo $app->render('bannedp.html', ['banned' => $bannedp]);
})->name('players-banned');
$app->get('/joueurs/bannis/ip', function() use($app) {
	$bannedi = json_decode(file_get_contents(BANNED_IPS_FILE), true);
	echo $app->render('bannedi.html', ['banned' => $bannedi]);
})->name('players-banned-ip');
$app->get('/joueurs/whitelist', function() use($app) {
	$whitelist = json_decode(file_get_contents(WHITELIST_FILE), true);
	$rawops = json_decode(file_get_contents(OP_FILE), true);
	$ops = [];
	foreach ($rawops as $op) {
		$ops[] = $op['name'];
	}
	echo $app->render('whitelist.html', ['whitelist' => $whitelist, 'ops' => $ops]);
})->name('players-whitelist');
$app->run();

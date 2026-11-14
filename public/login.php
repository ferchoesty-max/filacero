<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (operador_actual() !== null) {
    redirect('panel.php');
}

$usuario = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_post_csrf();
    $usuario = input_string($_POST, 'usuario', 40);
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($usuario === '' || $password === '') {
        $error = 'Escribe tu usuario y contraseña.';
    } else {
        $operador = verificar_credenciales($usuario, $password);
        if ($operador === null) {
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            iniciar_sesion($operador);
            set_flash('Bienvenido, ' . $operador['nombre'] . '. Estás en la Ventanilla ' . $operador['ventanilla'] . '.');
            redirect('panel.php');
        }
    }
}

$pageTitle = 'Acceso de operadores';
$input = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200';
require dirname(__DIR__) . '/src/views/header.php';
?>
<main class="max-w-md mx-auto w-full px-4 py-12">
  <p class="text-indigo-600 font-bold text-xs uppercase tracking-widest">Acceso restringido</p>
  <h1 class="text-3xl font-extrabold mt-1 mb-6">Entrar al panel</h1>

  <?php if ($error !== null): ?>
    <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 font-semibold p-4 mb-5"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post" action="login.php" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">
    <?= csrf_field() ?>
    <div>
      <label class="block font-bold text-sm mb-2" for="usuario">Usuario</label>
      <input id="usuario" name="usuario" class="<?= $input ?>" value="<?= e($usuario) ?>" maxlength="40" autocomplete="username" required autofocus>
    </div>
    <div>
      <label class="block font-bold text-sm mb-2" for="password">Contraseña</label>
      <input id="password" name="password" type="password" class="<?= $input ?>" autocomplete="current-password" required>
    </div>
    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition">Entrar</button>
  </form>
</main>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>

<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

ensure_post_csrf();
cerrar_sesion();
session_start();
session_regenerate_id(true);
set_flash('Sesión cerrada correctamente.');
redirect('login.php');

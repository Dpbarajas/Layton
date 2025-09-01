<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

$tipoBalance = $_GET['tipoBalance'] ?? '';
$idBalance = intval($_GET['idBalance'] ?? 0);

$stmt = $db->prepare("SELECT * FROM documento WHERE tipoBalance = ? AND idBalance = ?");
$stmt->execute([$tipoBalance, $idBalance]);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$noCargarNavBar = true;

$idDocumento = $documentos[0]['idDocumento'] ?? 0;

?>

<head>
    <meta charset="UTF-8">
    <title>ToniNet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/../assets/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="/assets/img/icono.png">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">

<div class="container-xxl">


    <?php if (empty($documentos)): ?>

        <div class="d-flex flex-column align-items-center justify-content-center text-center" style="height: 100%">
            <img style="red" src="/assets/img/empty-file.svg">
            <h4 class="mt-4 text-muted">No se han subido documentos aún</h4>
            <p class="text-secondary">Sube tus archivos para que aparezcan aquí.</p>
        </div>

    <?php else: ?>

        <div style="min-height: 95vh;" class="table-box d-flex justify-content-between">

            <div class="table-box me-2" style="overflow-y: scroll; width: 500px; flex: 0.5; height: 95vh;">
                <ul class="list-group">
                    <?php foreach ($documentos as $doc):
                        ?>
                        <li class="list-group-item doc-btn d-flex justify-content-between align-items-center"
                            data-path="<?= htmlspecialchars($doc['rutaArchivo']) ?>"
                            id="<?= $doc['idDocumento'] ?>">

                            <div class="text-truncate flex-grow-1 me-3">
                                <input type="text" readonly class="form-control-plaintext text-truncate nombres"
                                       value="<?= htmlspecialchars($doc['nombreArchivo']) ?>">
                            </div>

                            <div class="btn-group btn-group-sm flex-shrink-0" role="group"
                                 aria-label="Acciones documento">
                                <button data-id="<?= htmlspecialchars($doc['idDocumento']) ?>"
                                        class="btn btn-success cambiar-nombre">
                                    <img src="/assets/img/edit.svg" alt="Editar">
                                </button>
                                <button data-id="<?= htmlspecialchars($doc['idDocumento']) ?>"
                                        class="btn btn-danger eliminar-documento">
                                    <img src="/assets/img/delete-file.svg" alt="Eliminar">
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Visor -->
            <div style="flex: 1; height: 95vh; padding-left: 1rem;" class="table-box">
                <img id="viewer-img"
                     src=""
                     style="width: 100%; height: 100%; object-fit: contain; display: none;">
                <iframe id="viewer-pdf"
                        src=""
                        style="width: 100%; height: 100%; display: none;"></iframe>
            </div>

        </div>

    <?php endif; ?>

    <?php include '../includes/footer.php' ?>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            $(document).ready(function () {
                const $buttons = $('.doc-btn');
                const $viewerPDF = $('#viewer-pdf');
                const $viewerIMG = $('#viewer-img');

                $buttons.on('click', function () {

                    if ($(this).hasClass('active')) {
                        return;
                    }

                    const path = $(this).data('path');

                    if (path.endsWith("pdf")) {
                        $viewerIMG.attr('src', '').hide();
                        $viewerPDF.attr('src', path).show();
                    } else {
                        $viewerPDF.attr('src', '').hide();
                        $viewerIMG.attr('src', path).show();
                    }

                    $buttons.removeClass('active');
                    $(this).addClass('active');
                });

                if ($buttons.length > 0) {
                    $buttons.first().click();
                }
            });
        })
    </script>


    <script>
        $(document).ready(function () {
            $('.eliminar-documento').click(function (e) {
                e.stopPropagation();

                const $btn = $(this);
                const idDocumento = $btn.data('id');

                if (!confirm('¿Estás seguro de que quieres eliminar este documento?')) {
                    return;
                }

                $.post('accionDocumentos.php', {
                    accion: 'eliminar',
                    idDocumento: idDocumento
                })
                    .done(function (respuesta) {
                        if (respuesta.trim() === 'ok') {
                            $btn.closest('li').remove();
                            $('.doc-btn').first().click();
                        } else {
                            alert('Error al eliminar: ' + respuesta);
                        }
                    })
                    .fail(function () {
                        alert('Error de red o servidor.');
                    });
            });
        });
    </script>


    <script>
        $(document).ready(function () {
            $('.cambiar-nombre').click(function (e) {
                e.stopPropagation();

                const $allEditar = $('.cambiar-nombre')
                const $btnEditar = $(this);

                const $li = $btnEditar.closest('li');

                const $allEliminar = $('.eliminar-documento')
                const $btnEliminar = $li.find('.eliminar-documento');

                const $allInputs = $('.nombres')
                const $input = $li.find('input');

                $allInputs.prop('readonly', true);
                $allInputs.prop('disabled', false);
                $allInputs.prop('disabled', false);

                $allInputs.removeClass('form-control');
                $allInputs.addClass('form-control-plaintext');

                $allEditar.prop('disabled', false);
                $allEliminar.prop('disabled', false);

                $li.click();
                $input.removeClass('form-control-plaintext');
                $input.addClass('form-control');

                // Guarda el valor original
                const rutaOriginal = $li.data('path');
                const nombreOriginal = $input.val();

                // Desbloquea input
                $input.prop('readonly', false).focus();
                $btnEditar.prop('disabled', true);
                $btnEliminar.prop('disabled', true);

                // Maneja eventos de teclado
                $input.off('keydown').on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nuevoNombre = $input.val().trim();
                        const idDocumento = $btnEditar.data('id');

                        if (nuevoNombre === '') {
                            alert('El nombre no puede estar vacío.');
                            return;
                        }

                        // Llama a PHP
                        $.post('accionDocumentos.php', {
                            accion: 'cambiarNombre',
                            idDocumento: idDocumento,
                            nombre: nuevoNombre
                        })
                            .done(function (respuesta) {
                                console.log(respuesta);
                                debugger;
                                if (respuesta.trim() === 'ok') {
                                    // Cambiar la ruta del documento en el DOM
                                    const partesRuta = rutaOriginal.split('/');
                                    partesRuta.pop();
                                    partesRuta.push(nuevoNombre);

                                    const nuevaRuta = partesRuta.join('/');
                                    $li.attr('data-path', nuevaRuta);
                                } else {
                                    alert('Error al cambiar nombre: ' + respuesta);
                                    $input.val(nombreOriginal);
                                }
                            })
                            .fail(function () {
                                alert('Error de red.');
                                $input.val(nombreOriginal)
                            });

                    } else if (e.key === 'Escape') {
                        // Revertir cambios
                        $input.val(nombreOriginal);
                    }

                    if (e.key === 'Enter' || e.key === 'Escape') {
                        $input.removeClass('form-control');
                        $input.addClass('form-control-plaintext');

                        $input.prop('readonly', true);
                        $btnEditar.prop('disabled', false);
                        $btnEliminar.prop('disabled', false);
                    }
                });
            });
        });
    </script>

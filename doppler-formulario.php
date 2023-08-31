<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Formulario Doppler
 * Plugin URI:        https://github.com/MarcosT96/Doppler-Formulario
 * Description:       Plugin basico que permite agregar suscriptores a una lista de Doppler
 * Version:           1.0
 * Author:            Marcos Tomassi
 * Author URI:        https://grupo-met.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       doppler-formulario
 * Domain Path:       /languages
 */

function scripts_bootstrap()
{
    wp_enqueue_style('bootstrapCSS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrapJS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'scripts_bootstrap');

// Agregar el menú y la página de configuración
function menu_formulario_doppler()
{
    add_menu_page(
        'Configuración del Plugin',
        'Formulario Doppler',
        'manage_options',
        'formulario-doppler',
        'formulario_doppler_pagina_configuracion',
        'dashicons-email',
        20
    );
}
add_action('admin_menu', 'menu_formulario_doppler');

// Crear la página de configuración
function formulario_doppler_pagina_configuracion()
{
    // Guardar los valores de configuración
    if (isset($_POST['guardar_configuracion'])) {
        update_option('cuenta_correo', sanitize_email($_POST['cuenta_correo']));
        update_option('numero_lista', sanitize_text_field($_POST['numero_lista']));
        update_option('api_key', sanitize_text_field($_POST['api_key']));
        echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
    }

    // Obtener los valores de configuración actuales
    $cuenta_correo = get_option('cuenta_correo');
    $numero_lista = get_option('numero_lista');
    $api_key = get_option('api_key');
?>

    <div class="wrap">
        <h1>Configuración del Plugin</h1>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cuenta_correo">Correo de Cuenta:</label></th>
                    <td><input type="email" id="cuenta_correo" name="cuenta_correo" value="<?php echo esc_attr($cuenta_correo); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="numero_lista">Número de Lista:</label></th>
                    <td><input type="text" id="numero_lista" name="numero_lista" value="<?php echo esc_attr($numero_lista); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="api_key">API Key:</label></th>
                    <td><input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text"></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="guardar_configuracion" class="button-primary" value="Guardar Configuración">
            </p>
        </form>
    </div>
<?php
}

function form_doppler()
{
    // Datos del formulario
    $email = sanitize_email($_POST["email"]);
    $firstname = sanitize_text_field($_POST["firstname"]);
    $lastname = sanitize_text_field($_POST["lastname"]);
    $birthday = sanitize_text_field($_POST["birthday"]);

    // Validar el correo electrónico permitido
    $allowed_domains = array('gmail.com', 'outlook.com', 'hotmail.com');
    $email_domain = substr(strrchr($email, "@"), 1);
    if (!in_array($email_domain, $allowed_domains)) {
        echo "El correo electrónico no está permitido. Por favor, utiliza un correo de Gmail, Outlook o Hotmail.";
        return;
    }

    // Crear el cuerpo de la solicitud en formato JSON
    $data = array(
        "email" => $email,
        "fields" => array(
            array("name" => "FIRSTNAME", "value" => $firstname),
            array("name" => "LASTNAME", "value" => $lastname),
            array("name" => "BIRTHDAY", "value" => $birthday)
        )
    );

    // Obtener los valores de configuración actuales
    $cuenta_correo = get_option('cuenta_correo');
    $numero_lista = get_option('numero_lista');
    $api_key = get_option('api_key');

    $api_url = 'https://restapi.fromdoppler.com/accounts/' . $cuenta_correo . '/lists/' . $numero_lista . '/subscribers?api_key=' . $api_key;

    // Enviar la solicitud a la API
    $response = wp_safe_remote_post($api_url, array(
        'body' => json_encode($data),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    // Verificar si la suscripción fue exitosa
    if (is_wp_error($response)) {
        echo "Error al suscribirse. Por favor, inténtalo de nuevo más tarde.";
    } else {
        // Decodificar la respuesta JSON
        $api_response = json_decode(wp_remote_retrieve_body($response), true);

        // Verificar si el mensaje indica éxito
        if (isset($api_response['message']) && $api_response['message'] === "Subscriber successfully added to List") {
            echo '<div class="success-message">¡Suscripción exitosa!</div>';
        } elseif (isset($api_response['errorCode']) && $api_response['errorCode'] === 9) {
            echo "El cliente se ha desuscrito y no es posible volver a suscribirse por este medio.";
        } else {
            echo '<div class="error-message">Error al suscribirse. Por favor, inténtalo de nuevo más tarde.</div>';
        }
    }
}
function formulario_doppler_html()
{
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form_doppler"])) {
        // Llama a la función form_doppler() para procesar la suscripción
        form_doppler();
    }
    // Mostrar el formulario solo cuando se solicita el shortcode
    ob_start();
?>
    <style>
        .success-message {
            color: green;
            font-weight: bold;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
    <form id="form_doppler" method="post" class="needs-validation" novalidate>
        <div class="mb-3">
            <input placeholder="Nombre" type="text" id="firstname" name="firstname" class="form-control" required>
            <div class="invalid-feedback">
                Por favor ingresa tu nombre.
            </div>
        </div>

        <div class="mb-3">
            <input placeholder="Apellido" type="text" id="lastname" name="lastname" class="form-control" required>
            <div class="invalid-feedback">
                Por favor ingresa tu apellido.
            </div>
        </div>

        <div class="mb-3">
            <input placeholder="Fecha Nacimiento" type="date" id="birthday" name="birthday" class="form-control" required>
            <div class="invalid-feedback">
                Por favor ingresa tu fecha de nacimiento.
            </div>
        </div>
        <div class="mb-3">
            <input placeholder="Email" type="email" id="email" name="email" class="form-control" pattern="^[a-zA-Z0-9._%+-]+@(gmail|outlook|hotmail)\.com$" required>
            <div class="invalid-feedback">
                Por favor ingresa un email válido.
            </div>
        </div>

        <button type="submit" name="form_doppler" class="btn btn-primary">Suscribirse</button>
    </form>
<?php
    return ob_get_clean();
}

add_shortcode('formulario_doppler', 'formulario_doppler_html');

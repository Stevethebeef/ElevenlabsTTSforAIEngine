<?php
/**
 * Plugin Name: Text to Speech for AI Engine
 * Plugin URI: https://yourwebsite.com/
 * Description: This plugin converts AI Engine text responses to speech using ElevenLabs API.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * License: GPL2
 */

if (!defined('WPINC')) {
    die;
}

// Enqueue the chat-audio.js script
function tts_plugin_enqueue_scripts() {
    wp_enqueue_script(
        'chat-audio',
        plugin_dir_url(__FILE__) . 'chat-audio.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'tts_plugin_enqueue_scripts');

// Add the Text to Speech settings page
function tts_plugin_add_settings_page() {
    add_options_page(
        'Text to Speech Settings',
        'Text to Speech',
        'manage_options',
        'tts-plugin-settings',
        'tts_plugin_settings_page'
    );
}
add_action('admin_menu', 'tts_plugin_add_settings_page');

// Register plugin settings
function tts_plugin_register_settings() {
    register_setting('tts-plugin-settings-group', 'tts-plugin-api-key');
    register_setting('tts-plugin-settings-group', 'tts-plugin-voice-id');
}
add_action('admin_init', 'tts_plugin_register_settings');

// Render the settings page
function tts_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Text to Speech Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('tts-plugin-settings-group'); ?>
            <?php do_settings_sections('tts-plugin-settings-group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <?php tts_plugin_api_key_field_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Voice</th>
                    <td>
                        <?php tts_plugin_voice_id_field_callback(); ?>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// API Key field callback function
function tts_plugin_api_key_field_callback() {
    $api_key = get_option('tts-plugin-api-key');
    echo '<input type="text" name="tts-plugin-api-key" value="' . esc_attr($api_key) . '" />';
}

// Voice ID field callback function
function tts_plugin_voice_id_field_callback() {
    $api_key = get_option('tts-plugin-api-key');
    $voice_id = get_option('tts-plugin-voice-id');

    if (empty($api_key)) {
        echo 'Please enter your API Key to fetch the voices.';
    } else {
        $voices = tts_plugin_get_voices($api_key);

        if ($voices === false) {
            echo 'Failed to fetch voices. Please check your API Key.';
        } else {
            echo '<select name="tts-plugin-voice-id">';

            foreach ($voices as $voice) {
                $selected = ($voice_id === $voice['voice_id']) ? 'selected' : '';
                echo '<option value="' . esc_attr($voice['voice_id']) . '" ' . $selected . '>' . esc_html($voice['name']) . ' - ' . esc_html($voice['description']) . '</option>';
            }

            echo '</select>';
       
        }
    }
}

// Function to fetch voices from the ElevenLabs API
function tts_plugin_get_voices($api_key) {
    $url = 'https://api.elevenlabs.io/v1/voices';

    $response = wp_remote_get($url, [
        'headers' => [
            'xi-api-key' => $api_key,
            'Accept' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['voices'])) {
        return false;
    }

    return $data['voices'];
}

// Function to convert text to speech using ElevenLabs API
function tts_plugin_convert_to_speech($text) {
    $api_key = get_option('tts-plugin-api-key');
    $voice_id = get_option('tts-plugin-voice-id');

    if (empty($api_key) || empty($voice_id)) {
        return $text;
    }

    // Logging
    $log_message = "Converting text to speech: $text";
    tts_plugin_log($log_message);

    $url = 'https://api.elevenlabs.io/v1/text-to-speech/' . $voice_id;

$response = wp_remote_post($url, [
    'headers' => [
        'xi-api-key' => $api_key,
        'Content-Type' => 'application/json',
        'Accept' => 'audio/mpeg'
    ],
    'body' => json_encode([
        'text' => $text
    ]),
    'timeout' => 30 // Set the timeout to 30 seconds
]);


    if (!is_wp_error($response)) {
        $audio = wp_remote_retrieve_body($response);

        if (!empty($audio)) {
            $upload_dir = wp_upload_dir();
            $filename = 'tts-audio-' . time() . '.mp3';
            $file_path = $upload_dir['path'] . '/' . $filename;
            file_put_contents($file_path, $audio);

            $file_url = $upload_dir['url'] . '/' . $filename;

            // Logging
            $log_message = "MP3 file generated: $file_url";
            tts_plugin_log($log_message);

            return '<audio controls autoplay><source src="' . $file_url . '" type="audio/mpeg"></audio>';
        }
    }

    return $text;
}

// Filter the AI Engine reply to convert text to speech
function tts_plugin_convert_reply_to_speech($reply) {
    if (isset($reply->result)) {
        // Logging
        $log_message = "Original text: $reply->result";
        tts_plugin_log($log_message);

        $reply->result = tts_plugin_convert_to_speech($reply->result);

        // Logging
        $log_message = "Converted text: $reply->result";
        tts_plugin_log($log_message);
    }

    return $reply;
}
add_filter('mwai_ai_reply', 'tts_plugin_convert_reply_to_speech', 10, 1);

// Function to log messages to a file
function tts_plugin_log($message) {
    $log_file = wp_upload_dir()['basedir'] . '/tts-plugin-log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

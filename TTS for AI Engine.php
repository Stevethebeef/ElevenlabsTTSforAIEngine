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

// Global variable to store service type
$GLOBALS['service_type'] = null;

// Enqueue the js script for autoplay settings
function tts_plugin_enqueue_scripts() {
    wp_enqueue_script(
        'autoplay-control',
        plugin_dir_url(__FILE__) . 'autoplay-control.js',
        array(),
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
    tts_plugin_log("Added Text to Speech settings page");
}
add_action('admin_menu', 'tts_plugin_add_settings_page');

// Register plugin settings
function tts_plugin_register_settings() {
    register_setting('tts-plugin-settings-group', 'tts-plugin-api-key');
    register_setting('tts-plugin-settings-group', 'tts-plugin-voice-id');
    register_setting('tts-plugin-settings-group', 'tts-plugin-log-activation');
    register_setting('tts-plugin-settings-group', 'tts-plugin-mp3-removal-interval');
    tts_plugin_log("Registered settings");
}
add_action('admin_init', 'tts_plugin_register_settings');

// Render the settings page
function tts_plugin_settings_page() {
    tts_plugin_log("Rendering settings page");
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
                <tr>
                    <th scope="row">Activate Log</th>
                    <td>
                        <?php tts_plugin_log_activation_field_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">MP3 Removal Interval (in hours)</th>
                    <td>
                        <?php tts_plugin_mp3_removal_interval_field_callback(); ?>
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
    tts_plugin_log("API Key field rendered");
}

// Voice ID field callback function
function tts_plugin_voice_id_field_callback() {
    $api_key = get_option('tts-plugin-api-key');
    $voice_id = get_option('tts-plugin-voice-id');

    if (empty($api_key)) {
        echo 'Please enter your API Key to fetch the voices.';
        tts_plugin_log("API Key empty");
    } else {
        $voices = tts_plugin_get_voices($api_key);

        if (is_wp_error($voices)) {
            echo '<p class="error">Error fetching voices: ' . $voices->get_error_message() . '</p>';
            tts_plugin_log("Error fetching voices: " . $voices->get_error_message());
        } else {
            echo '<select name="tts-plugin-voice-id">';

            foreach ($voices as $voice) {
                $selected = ($voice_id === $voice['voice_id']) ? 'selected' : '';
                echo '<option value="' . esc_attr($voice['voice_id']) . '" ' . $selected . '>' . esc_html($voice['name']) . ' - ' . esc_html($voice['description']) . '</option>';
            }

            echo '</select>';
            tts_plugin_log("Voice ID field rendered");
        }
    }
}

// Log Activation field callback function
function tts_plugin_log_activation_field_callback() {
    $log_activation = get_option('tts-plugin-log-activation');
    echo '<input type="checkbox" name="tts-plugin-log-activation" value="1" ' . checked(1, $log_activation, false) . ' />';
    tts_plugin_log("Log Activation field rendered");
}

// MP3 Removal Interval field callback function
function tts_plugin_mp3_removal_interval_field_callback() {
    $interval = get_option('tts-plugin-mp3-removal-interval');
    echo '<input type="number" min="1" name="tts-plugin-mp3-removal-interval" value="' . esc_attr($interval) . '" /> hours';
    tts_plugin_log("MP3 Removal Interval field rendered");
}

// Function to fetch voices from the ElevenLabs API
function tts_plugin_get_voices($api_key) {
    tts_plugin_log("Fetching voices from ElevenLabs API");
    $url = 'https://api.elevenlabs.io/v1/voices';

    $response = wp_remote_get($url, [
        'headers' => [
            'xi-api-key' => $api_key,
            'Accept' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        tts_plugin_log("Error in fetching voices: " . $response->get_error_message());
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['voices'])) {
        tts_plugin_log("Failed to fetch voices");
        return new WP_Error('failed_fetch_voices', 'Failed to fetch voices.');
    }

    tts_plugin_log("Voices fetched successfully");
    return $data['voices'];
}

// Function to convert text to speech using ElevenLabs API
function tts_plugin_convert_to_speech($text) {
    $api_key = get_option('tts-plugin-api-key');
    $voice_id = get_option('tts-plugin-voice-id');

    if (empty($api_key) || empty($voice_id)) {
        tts_plugin_log("API Key or Voice ID is empty");
        return $text;
    }

    tts_plugin_log("Converting text to speech: $text");

    $url = 'https://api.elevenlabs.io/v1/text-to-speech/' . $voice_id;

    $response = wp_remote_post($url, [
        'headers' => [
            'xi-api-key' => $api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'audio/mpeg'
        ],
        'body' => json_encode([
            'text' => $text,
			'model_id'=> 'eleven_multilingual_v1'
		]),
        'timeout' => 30 // Set the timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        tts_plugin_log("Failed to convert text to speech: " . $response->get_error_message());
        return $text;
    } else {
        $audio = wp_remote_retrieve_body($response);

        if (!empty($audio)) {
            $upload_dir = wp_upload_dir();
            $filename = 'tts-audio-' . time() . '.mp3';
            $file_path = $upload_dir['path'] . '/' . $filename;
            file_put_contents($file_path, $audio);

            $file_url = $upload_dir['url'] . '/' . $filename;

            tts_plugin_log("MP3 file generated: $file_url");

            return '<audio controls autoplay><source src="' . $file_url . '" type="audio/mpeg"></audio>';
        }
    }

    tts_plugin_log("Failed to convert text to speech");
    return $text;
}

// Filter the AI Engine reply to convert text to speech
function tts_plugin_convert_reply_to_speech($reply) {
    // Log the raw input data
    tts_plugin_log("Received input: " . json_encode($reply, JSON_PRETTY_PRINT));

    // Check if the service type is 'tts' before converting to speech
    if (isset($reply->result)) {
        $original_text = $reply->result;
        $reply->result = tts_plugin_convert_to_speech($reply->result);

        // Log both the original and the converted text
        tts_plugin_log("Original text: $original_text");
        tts_plugin_log("Converted text: $reply->result");
    }

    // Log the output data
    tts_plugin_log("Returning output: " . json_encode($reply, JSON_PRETTY_PRINT));

    return $reply;
}
add_filter('mwai_ai_reply', 'tts_plugin_convert_reply_to_speech', 10, 1);


// Function to log messages to a file
function tts_plugin_log($message) {
    $log_activation = get_option('tts-plugin-log-activation');

    if ($log_activation) {
        $log_file = wp_upload_dir()['basedir'] . '/tts-plugin-log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Function to remove mp3 files periodically
function tts_plugin_remove_mp3_files() {
    tts_plugin_log("Started MP3 files removal");

    $interval = get_option('tts-plugin-mp3-removal-interval');
    $upload_dir = wp_upload_dir()['basedir'];
    $files = glob($upload_dir . '/tts-audio-*.mp3');

    foreach ($files as $file) {
        if (filemtime($file) < time() - 60 * 60 * $interval) {
            unlink($file);

            tts_plugin_log("Removed MP3 file: $file");
        }
    }

    tts_plugin_log("Finished MP3 files removal");
}
add_action('init', 'tts_plugin_remove_mp3_files');

// Schedule event to remove mp3 files periodically
if (!wp_next_scheduled('tts_plugin_remove_mp3_files')) {
    tts_plugin_log("Scheduled MP3 files removal");
    wp_schedule_event(time(), 'hourly', 'tts_plugin_remove_mp3_files');
}


// Filter the AI Engine query and log it
function tts_plugin_log_ai_query($query) {
    // Log the query data
    tts_plugin_log("Received AI query: " . json_encode($query, JSON_PRETTY_PRINT));

    // Save the service type globally
    if (isset($query->service)) {
        $GLOBALS['service_type'] = $query->service;
        tts_plugin_log("Service type: " . $GLOBALS['service_type']);
    }

    return $query;
}
add_filter('mwai_ai_query', 'tts_plugin_log_ai_query', 10, 1);

?>

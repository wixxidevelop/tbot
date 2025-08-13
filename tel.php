<?php
/**
 * Telegram Bot to Show User IDs
 * 
 * This bot responds to commands and shows users their Telegram ID
 * Works with webhooks or polling method
 */

class TelegramIDBot {
    private $botToken;
    private $apiURL;
    
    public function __construct($botToken) {
        $this->botToken = $botToken;
        $this->apiURL = "https://api.telegram.org/bot" . $this->botToken . "/";
    }
    
    /**
     * Send HTTP request to Telegram API
     */
    private function makeRequest($method, $parameters = []) {
        $url = $this->apiURL . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Send message to user
     */
    public function sendMessage($chatId, $text, $parseMode = 'Markdown') {
        $parameters = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        return $this->makeRequest('sendMessage', $parameters);
    }
    
    /**
     * Handle /start command
     */
    private function handleStart($update) {
        $user = $update['message']['from'];
        $chat = $update['message']['chat'];
        
        $firstName = $user['first_name'] ?? 'User';
        
        $message = "👋 Hello {$firstName}!\n\n";
        $message .= "🆔 Your Telegram ID: `{$user['id']}`\n";
        $message .= "💬 Chat ID: `{$chat['id']}`\n\n";
        $message .= "You can copy these IDs by tapping on them.\n\n";
        $message .= "Commands:\n";
        $message .= "/start - Show your IDs\n";
        $message .= "/id - Show your IDs\n";
        $message .= "/help - Show help message";
        
        $this->sendMessage($chat['id'], $message);
    }
    
    /**
     * Handle /id command
     */
    private function handleId($update) {
        $user = $update['message']['from'];
        $chat = $update['message']['chat'];
        
        if (in_array($chat['type'], ['group', 'supergroup', 'channel'])) {
            $message = "🆔 **Your User ID:** `{$user['id']}`\n";
            $message .= "👥 **Group/Channel ID:** `{$chat['id']}`\n";
            $message .= "📝 **Group/Channel Title:** " . ($chat['title'] ?? 'Unknown') . "\n";
            $message .= "📊 **Chat Type:** {$chat['type']}";
        } else {
            $message = "🆔 **Your Telegram ID:** `{$user['id']}`\n";
            $message .= "👤 **Username:** @" . ($user['username'] ?? 'None') . "\n";
            $message .= "📝 **First Name:** " . ($user['first_name'] ?? 'None') . "\n";
            $message .= "📝 **Last Name:** " . ($user['last_name'] ?? 'None');
        }
        
        $this->sendMessage($chat['id'], $message);
    }
    
    /**
     * Handle /help command
     */
    private function handleHelp($update) {
        $chat = $update['message']['chat'];
        
        $message = "🤖 **Telegram ID Bot Help**\n\n";
        $message .= "This bot helps you find your Telegram ID and chat IDs.\n\n";
        $message .= "**Commands:**\n";
        $message .= "/start - Welcome message with your ID\n";
        $message .= "/id - Show your user ID and chat information\n";
        $message .= "/help - Show this help message\n\n";
        $message .= "**Features:**\n";
        $message .= "• Works in private chats\n";
        $message .= "• Works in groups and channels\n";
        $message .= "• Shows user ID, chat ID, and additional info\n";
        $message .= "• IDs are formatted for easy copying\n\n";
        $message .= "Just send any of these commands to get your information!";
        
        $this->sendMessage($chat['id'], $message);
    }
    
    /**
     * Handle regular text messages
     */
    private function handleMessage($update) {
        $user = $update['message']['from'];
        $chat = $update['message']['chat'];
        
        $message = "🆔 Your ID: `{$user['id']}`\n";
        $message .= "💬 Chat ID: `{$chat['id']}`\n\n";
        $message .= "Use /id for more detailed information!";
        
        $this->sendMessage($chat['id'], $message);
    }
    
    /**
     * Process incoming update
     */
    public function processUpdate($update) {
        if (!isset($update['message'])) {
            return;
        }
        
        $message = $update['message'];
        $text = $message['text'] ?? '';
        
        // Handle commands
        if (strpos($text, '/start') === 0) {
            $this->handleStart($update);
        } elseif (strpos($text, '/id') === 0) {
            $this->handleId($update);
        } elseif (strpos($text, '/help') === 0) {
            $this->handleHelp($update);
        } elseif (!empty($text) && strpos($text, '/') !== 0) {
            // Handle regular messages (not commands)
            $this->handleMessage($update);
        }
    }
    
    /**
     * Set webhook for the bot
     */
    public function setWebhook($webhookUrl) {
        $parameters = [
            'url' => $webhookUrl
        ];
        
        return $this->makeRequest('setWebhook', $parameters);
    }
    
    /**
     * Remove webhook (for polling mode)
     */
    public function removeWebhook() {
        return $this->makeRequest('setWebhook', ['url' => '']);
    }
    
    /**
     * Get updates (for polling mode)
     */
    public function getUpdates($offset = 0) {
        $parameters = [
            'offset' => $offset,
            'timeout' => 30
        ];
        
        return $this->makeRequest('getUpdates', $parameters);
    }
    
    /**
     * Run bot in polling mode
     */
    public function runPolling() {
        echo "🚀 Starting Telegram ID Bot in polling mode...\n";
        echo "Press Ctrl+C to stop the bot\n\n";
        
        $this->removeWebhook(); // Remove webhook if exists
        
        $offset = 0;
        
        while (true) {
            $updates = $this->getUpdates($offset);
            
            if ($updates && isset($updates['result'])) {
                foreach ($updates['result'] as $update) {
                    $this->processUpdate($update);
                    $offset = $update['update_id'] + 1;
                }
            }
            
            sleep(1); // Small delay to prevent excessive API calls
        }
    }
}

// Configuration
$BOT_TOKEN = "8135112340:AAHvwvqU_0muChpkLfygH8SM47P9mdqFM8g";

// Initialize bot
$bot = new TelegramIDBot($BOT_TOKEN);

// Check if running via webhook or polling
if (isset($_POST) && !empty(file_get_contents('php://input'))) {
    // Webhook mode
    $update = json_decode(file_get_contents('php://input'), true);
    if ($update) {
        $bot->processUpdate($update);
    }
} else {
    // Polling mode (command line)
    if (php_sapi_name() === 'cli') {
        $bot->runPolling();
    } else {
        echo "Telegram ID Bot is running!\n";
        echo "Set up webhook or run from command line for polling mode.\n";
    }
}
?>

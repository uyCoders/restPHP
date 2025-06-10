<?php


class RateLimit {
    private static function getClientIP() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }
    
    public static function check() {
        $ip = self::getClientIP();
        $file = "rate_limits/{$ip}.txt";
        
        if (!file_exists('rate_limits')) {
            mkdir('rate_limits', 0777, true);
        }
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (time() - $data['time'] > 60) {
                $data = ['count' => 1, 'time' => time()];
            } else {
                $data['count']++;
            }
        } else {
            $data = ['count' => 1, 'time' => time()];
        }
        
        file_put_contents($file, json_encode($data));
        
        if ($data['count'] > 60) {
            header('HTTP/1.1 429 Too Many Requests');
            exit(json_encode(['error' => 'Rate limit exceeded']));
        }
    }
}

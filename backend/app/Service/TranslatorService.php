<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 国际化翻译服务
 * 
 * 简化的 i18n 实现，支持多语言翻译
 */
class TranslatorService
{
    private string $locale;
    private array $messages = [];
    private string $fallbackLocale = 'zh-CN';
    
    public function __construct(string $locale = 'zh-CN')
    {
        $this->locale = $locale;
        $this->loadMessages($locale);
    }
    
    /**
     * 翻译消息
     */
    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        
        // 尝试获取翻译
        $message = $this->messages[$locale][$key] ?? null;
        
        // 回退到默认语言
        if ($message === null && $locale !== $this->fallbackLocale) {
            $message = $this->messages[$this->fallbackLocale][$key] ?? null;
        }
        
        // 如果还是没有，返回键名
        if ($message === null) {
            return $key;
        }
        
        // 替换参数
        foreach ($params as $name => $value) {
            $message = str_replace("{{$name}}", (string)$value, $message);
        }
        
        return $message;
    }
    
    /**
     * 设置当前语言
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        
        if (!isset($this->messages[$locale])) {
            $this->loadMessages($locale);
        }
    }
    
    /**
     * 获取当前语言
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    
    /**
     * 加载语言文件
     */
    private function loadMessages(string $locale): void
    {
        $file = __DIR__ . "/../../resources/i18n/{$locale}/messages.php";
        
        if (file_exists($file)) {
            $this->messages[$locale] = require $file;
        } else {
            $this->messages[$locale] = [];
        }
    }
}

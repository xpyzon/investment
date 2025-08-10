<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}

function now_iso8601(): string
{
    return gmdate('c');
}

function bearer_token(): ?string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($hdr, 'Bearer ') === 0) {
        return trim(substr($hdr, 7));
    }
    return null;
}

function current_user(PDO $pdo): ?array
{
    $token = bearer_token();
    if (!$token) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token = :t LIMIT 1');
    $stmt->execute([':t' => $token]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_auth(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        json_response(['message' => 'Unauthenticated'], 401);
        exit;
    }
    if (!(int)($user['is_active'] ?? 1)) {
        json_response(['message' => 'Account disabled'], 403);
        exit;
    }
    return $user;
}

function require_admin(PDO $pdo): array
{
    $user = require_auth($pdo);
    if (($user['role'] ?? 'investor') !== 'admin') {
        json_response(['message' => 'Forbidden'], 403);
        exit;
    }
    return $user;
}

function validate(array $data, array $rules): array
{
    // Very lightweight validator. Only supports: required, string, integer, boolean, numeric, email, max:N, url
    $out = [];
    foreach ($rules as $field => $ruleStr) {
        $rulesArr = array_filter(array_map('trim', explode('|', $ruleStr)));
        $valuePresent = array_key_exists($field, $data);
        $value = $valuePresent ? $data[$field] : null;

        $isRequired = in_array('required', $rulesArr, true);
        if ($isRequired && !$valuePresent) {
            throw new InvalidArgumentException("$field is required");
        }
        if (!$valuePresent) {
            continue;
        }

        foreach ($rulesArr as $rule) {
            if ($rule === 'string' && !is_string($value)) throw new InvalidArgumentException("$field must be string");
            if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) throw new InvalidArgumentException("$field must be integer");
            if ($rule === 'boolean' && !is_bool($value) && !in_array($value, [0,1,'0','1'], true)) throw new InvalidArgumentException("$field must be boolean");
            if ($rule === 'numeric' && !is_numeric($value)) throw new InvalidArgumentException("$field must be numeric");
            if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException("$field must be a valid email");
            if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("$field must be a valid URL");
            if (str_starts_with($rule, 'max:')) {
                $max = (int)substr($rule, 4);
                if (is_string($value) && mb_strlen($value) > $max) throw new InvalidArgumentException("$field max length $max");
                if (is_numeric($value) && $value > $max) throw new InvalidArgumentException("$field must be <= $max");
            }
            if (str_starts_with($rule, 'min:')) {
                $min = (int)substr($rule, 4);
                if (is_numeric($value) && $value < $min) throw new InvalidArgumentException("$field must be >= $min");
            }
        }
        $out[$field] = $value;
    }
    return $out;
}

function json_try(callable $fn): void
{
    try {
        $fn();
    } catch (InvalidArgumentException $e) {
        json_response(['message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
        json_response(['message' => 'Server error'], 500);
    }
}
<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Exception;

class GoogleAuthService
{
    private string $googleClientId;
    private string $googleClientSecret;

    public function __construct()
    {
        $this->googleClientId = "66107300806-ut64q3vdqqh0krb2jkgdvdv3ng694d4r.apps.googleusercontent.com";
    }

    /*public function getGooglePublicKeys(): array
    {
        $keysJson = file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
        $keys = json_decode($keys, true);
        if (!$keys || !isset($keys['keys'])) {
            throw new \RuntimeException("Failed to fetch Google public keys.");
        }
        return \Firebase\JWT\JWT::decode()
    }*/

    public function verifyIdToken(string $idToken): array
    {
        try {
            // Get Google's public keys
            $keys = file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
            if (!$keys) {
                throw new Exception("Failed to fetch Google public keys.");
            }

            $keys = json_decode($keys, true);
//            $keys = $this->getGooglePublicKeys();
            $decoded = \Firebase\JWT\JWT::decode($idToken, JWK::parseKeySet($keys));

            // Verify issuer and audience
            if ($decoded->iss !== "https://accounts.google.com" && $decoded->iss !== "accounts.google.com") {
                throw new Exception("Invalid issuer.");
            }
            if ($decoded->aud !== $this->googleClientId) {
                throw new Exception("Invalid audience.");
            }

            // Convert object to an array
            return (array) $decoded;
        } catch (Exception $e) {
            throw new Exception("Token verification failed: " . $e->getMessage());
        }
    }

    public function verifyWithGoogleEndpoint(string $idToken): ?array
    {
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;
        $response = file_get_contents($url);
        if (!$response) {
            return null; // Google endpoint not reachable
        }

        $payload = json_decode($response, true);
        if (!$payload || !isset($payload['sub'])) {
            return null; // Invalid token
        }

        // Check audience
        if ($payload['aud'] !== $this->googleClientId) {
            return null; // Invalid audience
        }

        return $payload; // Google token is valid
    }


    public function getUserData(string $idToken): ?array
    {
       $payload = $this->verifyIdToken($idToken);
//        $payload = $this->verifyWithGoogleEndpoint($idToken);
        if ($payload) {
            return [
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'name' => $payload['name'] ?? null,
            ];
        }
        return null;
    }
}


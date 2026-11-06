<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class TrustCloudflareClientIp extends TrustProxies
{
    public function handle(Request $request, Closure $next)
    {
        $peer = $request->server->get('REMOTE_ADDR');
        $ranges = config('cloudflare.proxies', []);

        if (! config('cloudflare.enabled') || ! is_string($peer)
            || ! filter_var($peer, FILTER_VALIDATE_IP) || ! IpUtils::checkIp($peer, $ranges)) {
            return parent::handle($request, $next);
        }

        $client = $request->headers->get('CF-Connecting-IP');
        // Pseudo IPv4 can replace the real IPv6 address with an address in 240/4.
        if (is_string($client) && filter_var($client, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && IpUtils::checkIp($client, '240.0.0.0/4')) {
            $ipv6 = $request->headers->get('CF-Connecting-IPv6');
            if (is_string($ipv6) && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $client = $ipv6;
            }
        }

        // Only a verified Cloudflare peer may supply the single visitor address.
        // Ignore incoming XFF chains, including visitor-supplied values.
        $request->headers->remove('X-Forwarded-For');
        if (is_string($client) && filter_var($client, FILTER_VALIDATE_IP)) {
            $request->headers->set('X-Forwarded-For', $client);
        }

        $request::setTrustedProxies([$peer], Request::HEADER_X_FORWARDED_FOR);

        return $next($request);
    }
}

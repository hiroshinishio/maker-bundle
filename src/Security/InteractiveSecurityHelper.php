<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class InteractiveSecurityHelper
{
    public function guessFirewallName(SymfonyStyle $io, array $securityData): string
    {
        $realFirewalls = array_filter(
            $securityData['security']['firewalls'] ?? [],
            function ($item) {
                return !isset($item['security']) || true === $item['security'];
            }
        );

        if (0 === \count($realFirewalls)) {
            return 'main';
        }

        if (1 === \count($realFirewalls)) {
            return key($realFirewalls);
        }

        return $io->choice('Which firewall do you want to update ?', array_keys($realFirewalls), key($realFirewalls));
    }

    public function guessEntryPoint(SymfonyStyle $io, array $securityData, string $authenticatorClass, string $firewallName)
    {
        if (!isset($securityData['security'])) {
            $securityData['security'] = [];
        }

        if (!isset($securityData['security']['firewalls'])) {
            $securityData['security']['firewalls'] = [];
        }

        $firewalls = $securityData['security']['firewalls'];
        if (!isset($firewalls[$firewallName])) {
            throw new RuntimeCommandException(sprintf('Firewall "%s" does not exist', $firewallName));
        }

        if (!isset($firewalls[$firewallName]['guard'])
            || !isset($firewalls[$firewallName]['guard']['authenticators'])
            || !$firewalls[$firewallName]['guard']['authenticators']
            || isset($firewalls[$firewallName]['guard']['entry_point'])) {
            return null;
        }

        $authenticators = $firewalls[$firewallName]['guard']['authenticators'];
        $authenticators[] = $authenticatorClass;

        return $io->choice(
            'The entry point for your firewall is what should happen when an anonymous user tries to access
a protected page. For example, a common "entry point" behavior is to redirect to the login page.
The "entry point" behavior is controlled by the start() method on your authenticator.
However, you will now have multiple authenticators. You need to choose which authenticator\'s
start() method should be used as the entry point (the start() method on all other
authenticators will be ignored, and can be blank.',
            $authenticators,
            current($authenticators)
        );
    }
}
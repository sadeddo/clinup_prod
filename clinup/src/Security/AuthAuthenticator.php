<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName,Security $security): ?Response
{
    if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
        error_log('Redirecting to target path: ' . $targetPath);
        return new RedirectResponse($targetPath);
    }

    $user = $security->getUser();
    $roles = $user->getRoles();

    // Ajout de logs pour vérifier les rôles de l'utilisateur
    error_log('User roles: ' . implode(', ', $roles));

    if (in_array("ROLE_PRESTATAIRE", $roles)) {
        error_log('Redirecting to prestataire route');
        return new RedirectResponse($this->urlGenerator->generate('app_reservation_prestataire'));
    } elseif (in_array("ROLE_HOTE", $roles)) {
        error_log('Redirecting to hote route');
        return new RedirectResponse($this->urlGenerator->generate('app_logement_index'));
    } elseif (in_array("ROLE_ADMIN", $roles)) {
        error_log('Redirecting to admin route');
        return new RedirectResponse($this->urlGenerator->generate('app_admin'));
    }

    throw new \Exception('TODO: provide a valid redirect inside ' . __FILE__);
}

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

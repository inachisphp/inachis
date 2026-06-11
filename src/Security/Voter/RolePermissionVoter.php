<?php

namespace Inachis\Security\Voter;

use Inachis\Entity\Security\RolePermission;
use Inachis\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for testing role permission
 * 
 * @extends Voter<string, object|null>
 */
class RolePermissionVoter extends Voter
{
    // Define supported attributes; could be extended as needed
    private const SUPPORTED = [
        // Page actions
        'PAGE_VIEW',
        'PAGE_CREATE',
        'PAGE_EDIT',
        'PAGE_DELETE',
        // Series actions
        'SERIES_VIEW',
        'SERIES_CREATE',
        'SERIES_EDIT',
        'SERIES_DELETE',
        // Review actions (if a Review entity exists later)
        'REVIEW_VIEW',
        'REVIEW_CREATE',
        'REVIEW_EDIT',
        'REVIEW_DELETE',
        // Role management actions
        'ROLE_VIEW',
        'ROLE_CREATE',
        'ROLE_EDIT',
        'ROLE_DELETE',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        // We only care about the predefined attributes; subject can be null or any entity
        return in_array($attribute, self::SUPPORTED, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // not logged in or not a User entity
            return false;
        }

        $role = $user->getRole();
        if (null === $role) {
            return false;
        }

        // Map attribute to action/resource pairs expected in RolePermission
        $map = [
            'PAGE_VIEW'   => ['action' => 'VIEW',   'resource' => 'PAGE'],
            'PAGE_CREATE' => ['action' => 'CREATE', 'resource' => 'PAGE'],
            'PAGE_EDIT'   => ['action' => 'EDIT',   'resource' => 'PAGE'],
            'PAGE_DELETE' => ['action' => 'DELETE', 'resource' => 'PAGE'],
            'SERIES_VIEW'   => ['action' => 'VIEW',   'resource' => 'SERIES'],
            'SERIES_CREATE' => ['action' => 'CREATE', 'resource' => 'SERIES'],
            'SERIES_EDIT'   => ['action' => 'EDIT',   'resource' => 'SERIES'],
            'SERIES_DELETE' => ['action' => 'DELETE', 'resource' => 'SERIES'],
            'REVIEW_VIEW'   => ['action' => 'VIEW',   'resource' => 'REVIEW'],
            'REVIEW_CREATE' => ['action' => 'CREATE', 'resource' => 'REVIEW'],
            'REVIEW_EDIT'   => ['action' => 'EDIT',   'resource' => 'REVIEW'],
            'REVIEW_DELETE' => ['action' => 'DELETE', 'resource' => 'REVIEW'],
            'ROLE_VIEW'   => ['action' => 'VIEW',   'resource' => 'ROLE'],
            'ROLE_CREATE' => ['action' => 'CREATE', 'resource' => 'ROLE'],
            'ROLE_EDIT'   => ['action' => 'EDIT',   'resource' => 'ROLE'],
            'ROLE_DELETE' => ['action' => 'DELETE', 'resource' => 'ROLE'],
        ];

        if (!isset($map[$attribute])) {
            return false;
        }

        $requiredAction = $map[$attribute]['action'];
        $requiredResource = $map[$attribute]['resource'];

        foreach ($role->getRolePermissions() as $perm) {
            if ($perm->getAction() === $requiredAction && $perm->getResource() === $requiredResource) {
                return true;
            }
        }

        return false;
    }
}
?>

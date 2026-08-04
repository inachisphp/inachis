<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content;

use Inachis\Entity\User\User;
use Inachis\Entity\User\UserViewState;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\User\UserViewStateRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class ViewStateManager implements ViewStateManagerInterface
{
    public function __construct(
        private Security $security,
        private UserViewStateRepository $repository,
    ) {
    }

    /**
     * Loads content view settings. It will load the defaults for this view,
     * apply session-stored values over the top if set, and if not set, will
     * check the database for stored values and apply those instead over the
     * top if set.
     *
     * Priority: POST > Session > DB > Defaults
     */
    public function load(
        Request $request,
        string $context,
        ViewStateDefaults $defaults,
    ): ContentQueryParameters {
        $state = [
            'filters' => $defaults->getFilters(),
            'sort' => $defaults->getSort(),
            'view' => $defaults->getView(),
        ];
        $session = $request->getSession();
        $sessionState = $session->get("view_state.$context", null);

        if (is_array($sessionState)) {
            $state = array_replace_recursive($state, $sessionState);
        } else {
            /*
            * Database
            */
            /** @var User|null $user */
            $user = $this->security->getUser();

            if ($user instanceof User) {
                $saved = $this->repository->findFor(
                    $user,
                    $context,
                );

                if (null !== $saved) {
                    $state = array_replace_recursive(
                        $state,
                        $saved->getState(),
                    );

                    /*
                    * Warm the session cache.
                    */
                    $session->set(
                        "view_state.$context",
                        $saved->getState(),
                    );
                }
            }
        }

        /*
        * Request overrides.
        */
        $requestState = [
            'filters' => array_filter(
                $request->request->all('filter'),
            ),
            'sort' => $request->request->getString('sort'),
            'view' => $request->request->getString('view'),
        ];

        $requestState = array_filter(
            $requestState,
            static fn (mixed $value): bool => '' !== $value && [] !== $value,
        );

        if ([] !== $requestState) {
            $state = array_replace_recursive(
                $state,
                $requestState,
            );
        }

        return new ContentQueryParameters(
            filters: $state['filters'],
            sort: $state['sort'],
            limit: $request->attributes->getInt('limit', 10),
            offset: $request->attributes->getInt('offset', 0),
            view: $state['view'],
        );
    }

    /**
     * Update the session and database with the current View settings.
     */
    public function save(
        SessionInterface $session,
        string $context,
        ContentQueryParameters $parameters,
    ): void {
        $state = [
            'filters' => $parameters->getFilters(),
            'sort' => $parameters->getSort(),
            'view' => $parameters->getView(),
        ];

        /*
        * Update the session first. This acts as our cache so subsequent
        * requests don't need to query the database.
        */
        $session->set("view_state.$context", $state);

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $saved = $this->repository->findFor(
            $user,
            $context,
        );

        if (null === $saved) {
            $saved = new UserViewState($user, $context);
        }
        $saved->setState($state);

        $this->repository->save($saved);
    }

    /**
     * Clears the Session and DB for the specified context for this user.
     */
    public function clear(
        SessionInterface $session,
        string $context,
    ): void {
        $session->remove("view_state.$context");

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $saved = $this->repository->findFor(
            $user,
            $context,
        );

        if (null !== $saved) {
            $this->repository->remove($saved);
        }
    }

    /**
     * Loads and returns {@link ContentQueryParameters} for the current
     * request context.
     */
    public function build(
        Request $request,
        string $context,
        ViewStateDefaults $defaults,
        CategoryRepository $categoryRepository,
    ): ContentQueryParameters {
        $state = $this->load($request, $context, $defaults);

        return ContentQueryParameters::fromRequest($request, $state, $categoryRepository);
    }

    /**
     * Creates a DTO from the Request parameters and updates the
     * session and DB values for this context.
     */
    public function update(
        Request $request,
        string $context,
        ContentQueryParameters $current,
        CategoryRepository $categoryRepository,
    ): ContentQueryParameters {
        $params = ContentQueryParameters::fromRequest(
            $request,
            $current,
            $categoryRepository,
        );
        $this->save($request->getSession(), $context, $params);

        return $params;
    }
}

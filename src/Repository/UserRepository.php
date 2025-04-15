<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends AbstractRepository //ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * Fetches a specific entity from the repository by the given Id and decrypts any encrypted fields
    //     * @param string The Id of the entity to be returned
    //     * @return mixed The returned entity
    //     */
    //    public function getById($id)
    //    {
    //        $user = $this->getByIdRaw($id);
    //        $this->decryptFields($user);
    //        return $user;
    //    }
    //    /**
    //     * Fetches a specific entity from the repository by the given Id
    //     * @param string The Id of the entity to be returned
    //     * @return mixed The returned entity
    //     */
    //    public function getByIdRaw($id)
    //    {
    //        $user = $this->getRepository()->find($id);
    //        return $user;
    //    }
    //    /**
    //     * Returns a {@link User} based on the specified username and decrypts any encrypted fields
    //     * @param string $username The username of the {@link User} to return
    //     * @return User The retrieved user object
    //     */
    //    public function getByUsername($username)
    //    {
    //        $user = $this->getRepository()->findOneBy(array('username' => $username));
    //        $this->decryptFields($user);
    //        return $user;
    //    }
}

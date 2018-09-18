<?php namespace App\Api;

use App\Authorization\UserRules;
use App\Data\Models\RoleScope;
use App\Data\Models\User as Model;
use App\Json\Schemas\UserSchema as Schema;
use Doctrine\DBAL\Connection;
use Limoncello\Contracts\Exceptions\AuthorizationExceptionInterface;
use Limoncello\Crypt\Contracts\HasherInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package App
 */
class UsersApi extends BaseApi
{
    /**
     * @param ContainerInterface $container
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, Model::class);
    }

    /**
     * @inheritdoc
     *
     * @throws AuthorizationExceptionInterface
     */
    public function create($index, iterable $attributes, iterable $toMany): string
    {
        $this->authorize(UserRules::ACTION_MANAGE_USERS, Schema::TYPE);

        return parent::create($index, $this->getReplacePasswordWithHash($attributes), $toMany);
    }

    /**
     * @inheritdoc
     *
     * @throws AuthorizationExceptionInterface
     */
    public function update($index, iterable $attributes, iterable $toMany): int
    {
        $this->authorize(UserRules::ACTION_MANAGE_USERS, Schema::TYPE, $index);

        return parent::update($index, $this->getReplacePasswordWithHash($attributes), $toMany);
    }

    /**
     * @inheritdoc
     *
     * @throws AuthorizationExceptionInterface
     */
    public function remove($index): bool
    {
        $this->authorize(UserRules::ACTION_MANAGE_USERS, Schema::TYPE, $index);

        return parent::remove($index);
    }

    /**
     * @inheritdoc
     *
     * @throws AuthorizationExceptionInterface
     */
    public function index(): PaginatedDataInterface
    {
        $this->authorize(UserRules::ACTION_VIEW_USERS, Schema::TYPE);

        return parent::index();
    }

    /**
     * @inheritdoc
     *
     * @throws AuthorizationExceptionInterface
     */
    public function read($index)
    {
        $this->authorize(UserRules::ACTION_VIEW_USERS, Schema::TYPE, $index);

        return parent::read($index);
    }

    /**
     * @param int $userId
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function noAuthReadScopes(int $userId): array
    {
        /** @var Connection $connection */
        $connection  = $this->getContainer()->get(Connection::class);
        $query       = $connection->createQueryBuilder();
        $users       = 'u';
        $uRoleId     = Model::FIELD_ID_ROLE;
        $rolesScopes = 'rs';
        $rsRoleId    = RoleScope::FIELD_ID_ROLE;
        $query
            ->select(RoleScope::FIELD_ID_SCOPE)
            ->from(Model::TABLE_NAME, $users)
            ->leftJoin($users, RoleScope::TABLE_NAME, $rolesScopes, "$users.$uRoleId = $rolesScopes.$rsRoleId")
            ->where(Model::FIELD_ID . '=' . $query->createPositionalParameter($userId, PDO::PARAM_INT));

        $scopes = array_column($query->execute()->fetchAll(), RoleScope::FIELD_ID_SCOPE);

        return $scopes;
    }

    /**
     * @param string $email
     *
     * @return int|null
     */
    public function noAuthReadUserIdByEmail(string $email): ?int
    {
        /** @var Connection $connection */
        $connection  = $this->getContainer()->get(Connection::class);
        $query       = $connection->createQueryBuilder();
        $query
            ->select(Model::FIELD_ID)
            ->from(Model::TABLE_NAME)
            ->where(Model::FIELD_EMAIL . '=' . $query->createPositionalParameter($email))
            ->setMaxResults(1);
        $statement = $query->execute();
        $idOrFalse = $statement->fetchColumn();
        $userId    = $idOrFalse === false ? null : (int)$idOrFalse;

        return $userId;
    }

    /**
     * @param iterable $attributes
     *
     * @return iterable
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getReplacePasswordWithHash(iterable $attributes): iterable
    {
        // in attributes were captured validated input password we need to convert it into password hash
        foreach ($attributes as $name => $value) {
            if ($name === Schema::CAPTURE_NAME_PASSWORD) {
                /** @var HasherInterface $hasher */
                $hasher = $this->getContainer()->get(HasherInterface::class);
                $value  = $hasher->hash($value);
                $name   = Model::FIELD_PASSWORD_HASH;
            }

            yield $name => $value;
        }
    }
}

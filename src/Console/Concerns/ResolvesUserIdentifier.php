<?php

namespace BaseApi\Console\Concerns;

use BaseApi\App;
use BaseApi\Auth\UserProvider;
use BaseApi\Models\BaseModel;
use Exception;

/**
 * Resolves a CLI user argument (id or email) to a user id for the perm:* commands.
 */
trait ResolvesUserIdentifier
{
    private function resolveUserId(string $identifier): ?string
    {
        $userProvider = App::container()->make(UserProvider::class);
        if ($userProvider->byId($identifier) !== null) {
            return $identifier;
        }

        if (!str_contains($identifier, '@')) {
            return null;
        }

        try {
            $table = $this->userTable();
            $result = App::db()->raw(sprintf('SELECT id FROM `%s` WHERE email = ? LIMIT 1', $table), [$identifier]);
        } catch (Exception) {
            return null;
        }

        return $result === [] ? null : (string) $result[0]['id'];
    }

    /**
     * The app's user table. Model tables are singular (`User` -> `user`), so this
     * asks the app's User model instead of assuming a name.
     */
    private function userTable(): string
    {
        $model = 'App\\Models\\User';
        if (class_exists($model) && is_subclass_of($model, BaseModel::class)) {
            return $model::table();
        }

        return 'user';
    }
}

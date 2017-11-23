<?php

namespace OpenCFP\Infrastructure\Auth;

use Cartalyst\Sentinel\Sentinel;
use Illuminate\Support\Collection;

final class SentinelUser implements UserInterface
{
    /**
     * @var \Cartalyst\Sentinel\Users\EloquentUser
     */
    private $user;

    /**
     * @var \Cartalyst\Sentinel\Sentinel
     */
    private $sentinel;

    public function __construct(\Cartalyst\Sentinel\Users\UserInterface $user, Sentinel $sentinel)
    {
        $this->user     = $user;
        $this->sentinel = $sentinel;
    }

    public function getId(): int
    {
        return $this->user->getUserId();
    }

    public function getLogin(): string
    {
        return $this->user->getUserLogin();
    }

    /**
     * @param string $permissions
     *
     * @return bool
     */
    public function hasAccess($permissions): bool
    {
        try {
            /** @var Collection | \Cartalyst\Sentinel\Users\EloquentUser[] $users */
            $users = $this->sentinel->getRoleRepository()->findByName($permissions)->getUsers();

            return $users->contains(function ($user) {
                return $user->id == $this->user->id;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function checkPassword(string $password): bool
    {
        return $this->sentinel
            ->getUserRepository()
            ->validateCredentials($this->getUser(), ['password' => $password]);
    }

    /**
     * Checks if the provided user reset password code is
     * valid without actually resetting the password.
     *
     * @param string $resetCode
     *
     * @return bool
     */
    public function checkResetPasswordCode(string $resetCode): bool
    {
        return $this->sentinel->getReminderRepository()->exists($this->user, $resetCode) !== false;
    }

    /**
     * Create a password reset code for the user, or reset it if it already exists
     * This will NOT retrieve the code if it is already set, it will instead generate a new one and set that.
     */
    public function getResetPasswordCode(): string
    {
        return $this->sentinel->getReminderRepository()->create($this->user);
    }

    /**
     * Attempts to reset a user's password by matching
     * the reset code generated with the user's.
     *
     * @param string $resetCode
     * @param string $newPassword
     *
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword): bool
    {
        return $this->sentinel->getReminderRepository()->complete($this->user, $resetCode, $newPassword);
    }

    /**
     * @return \Cartalyst\Sentinel\Users\EloquentUser|\Cartalyst\Sentinel\Users\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}

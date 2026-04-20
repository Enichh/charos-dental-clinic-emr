<?php

namespace CharosEMR\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\LoginPatientRequest;
use CharosEMR\Application\User\DTOs\LoginPatientResponse;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;

class LoginPatientUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger
    ) {}

    /** Authenticate patient with email and password */
    public function execute(LoginPatientRequest $request): LoginPatientResponse
    {
        $this->logger->info('Patient login attempt', ['email' => $request->email]);

        $user = $this->userRepository->findByEmail($request->email);
        if ($user === null) {
            $this->logger->warning('Login failed - user not found', ['email' => $request->email]);
            throw new \InvalidArgumentException('Invalid credentials');
        }

        if (!$user->isActive()) {
            $this->logger->warning('Login failed - inactive user', ['email' => $request->email, 'user_id' => $user->getId()]);
            throw new \InvalidArgumentException('Invalid credentials');
        }

        if (!$this->passwordHasher->verify($request->password, $user->getPasswordHash())) {
            $this->logger->warning('Login failed - invalid password', ['email' => $request->email, 'user_id' => $user->getId()]);
            throw new \InvalidArgumentException('Invalid credentials');
        }

        $user->updateLastLogin();
        $this->userRepository->save($user);

        $this->logger->info('Patient login successful', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->value
        ]);

        return new LoginPatientResponse(
            $user->getId(),
            $user->getEmail(),
            $user->getRole()->value,
            'Login successful'
        );
    }
}

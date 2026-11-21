<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Area;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\User\Model\UserFactory;

/**
 * Works out who is acting: the logged-in admin, or the admin token / integration behind an API call.
 * Customer and guest API calls resolve to null and are never logged.
 */
class ActorResolver
{
    public const TYPE_ADMIN = 'admin';
    public const TYPE_INTEGRATION = 'integration';

    /** @var array{user_type:string, user_id:int|null, username:string}|null */
    private ?array $actor = null;

    public function __construct(
        private readonly Session $authSession,
        private readonly UserContextInterface $userContext,
        private readonly UserFactory $userFactory,
        private readonly IntegrationServiceInterface $integrationService
    ) {
    }

    /**
     * @return array{user_type:string, user_id:int|null, username:string}|null
     */
    public function resolve(string $area): ?array
    {
        if ($this->actor !== null) {
            return $this->actor;
        }

        // Null results are not cached: during login the admin session is set part-way through the request.
        $this->actor = $area === Area::AREA_ADMINHTML ? $this->fromAdminSession() : $this->fromApiContext();

        return $this->actor;
    }

    private function fromAdminSession(): ?array
    {
        $user = $this->authSession->getUser();
        if (!$user || !$user->getId()) {
            return null;
        }

        return [
            'user_type' => self::TYPE_ADMIN,
            'user_id' => (int)$user->getId(),
            'username' => (string)$user->getUserName(),
        ];
    }

    private function fromApiContext(): ?array
    {
        $userId = (int)$this->userContext->getUserId();
        $userType = (int)$this->userContext->getUserType();
        if ($userId <= 0) {
            return null;
        }

        if ($userType === UserContextInterface::USER_TYPE_ADMIN) {
            $user = $this->userFactory->create()->load($userId);

            return [
                'user_type' => self::TYPE_ADMIN,
                'user_id' => $userId,
                'username' => (string)$user->getUserName() ?: 'admin #' . $userId,
            ];
        }

        if ($userType === UserContextInterface::USER_TYPE_INTEGRATION) {
            try {
                $name = (string)$this->integrationService->get($userId)->getName();
            } catch (\Throwable) {
                $name = '';
            }

            return [
                'user_type' => self::TYPE_INTEGRATION,
                'user_id' => $userId,
                'username' => $name !== '' ? $name : 'integration #' . $userId,
            ];
        }

        return null;
    }
}

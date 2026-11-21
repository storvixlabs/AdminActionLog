<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Writes log rows. Decides whether the current request should be logged (area + actor),
 * and never lets a logging failure break the admin action itself.
 */
class ActionLogger
{
    public const TABLE = 'storvixlabs_admin_action_log';

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_MASS_UPDATE = 'mass_update';
    public const ACTION_LOGIN = 'login_success';
    public const ACTION_LOGIN_FAILED = 'login_failed';

    private const API_AREAS = [Area::AREA_WEBAPI_REST, Area::AREA_WEBAPI_SOAP];

    public function __construct(
        private readonly Config $config,
        private readonly State $appState,
        private readonly ActorResolver $actorResolver,
        private readonly ResourceConnection $resource,
        private readonly RemoteAddress $remoteAddress,
        private readonly HttpRequest $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Area and actor for the current request, or null if it must not be logged.
     *
     * @return array{area:string, actor:array{user_type:string, user_id:int|null, username:string}}|null
     */
    public function getContext(): ?array
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        try {
            $area = $this->appState->getAreaCode();
        } catch (LocalizedException) {
            return null;
        }

        $isApi = in_array($area, self::API_AREAS, true);
        if ($area !== Area::AREA_ADMINHTML && !($isApi && $this->config->isLogApi())) {
            return null;
        }

        $actor = $this->actorResolver->resolve($area);

        return $actor === null ? null : ['area' => $area, 'actor' => $actor];
    }

    /**
     * @param array{user_type:string, user_id:int|null, username:string} $actor
     * @param array<int, array{field:string, old:string, new:string}> $changes
     */
    public function write(
        array $actor,
        string $area,
        string $action,
        string $entityType,
        ?string $entityId,
        ?string $entityLabel,
        array $changes = []
    ): void {
        try {
            $this->resource->getConnection()->insert(
                $this->resource->getTableName(self::TABLE),
                [
                    'created_at' => gmdate('Y-m-d H:i:s'),
                    'user_type' => $actor['user_type'],
                    'user_id' => $actor['user_id'],
                    'username' => mb_substr($actor['username'], 0, 255),
                    'ip' => mb_substr((string)$this->remoteAddress->getRemoteAddress(), 0, 45),
                    'area' => $area,
                    'action' => $action,
                    'entity_type' => mb_substr($entityType, 0, 64),
                    'entity_id' => $entityId === null ? null : mb_substr($entityId, 0, 64),
                    'entity_label' => $entityLabel === null ? null : mb_substr($entityLabel, 0, 255),
                    'request_path' => mb_substr($this->getRequestPath($area), 0, 255),
                    'changes' => $changes ? json_encode(
                        $changes,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                    ) : null,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('StorvixLabs Admin Action Log: could not write entry', ['exception' => $e]);
        }
    }

    private function getRequestPath(string $area): string
    {
        try {
            if ($area === Area::AREA_ADMINHTML) {
                return (string)$this->request->getFullActionName();
            }

            return $this->request->getMethod() . ' ' . $this->request->getPathInfo();
        } catch (\Throwable) {
            return '';
        }
    }
}

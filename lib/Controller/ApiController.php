<?php

declare(strict_types=1);

namespace OCA\NcPhotosServer\Controller;

use OCA\NcPhotosServer\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\App\IAppManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	public function __construct(
		IRequest $request,
		private LoggerInterface $logger,
		private ?\OCA\Recognize\Public\ApiKeyManager $apiKeyManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * An example API endpoint
	 *
	 * @return JSONResponse
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/recognize-api-key')]
	public function index(): JSONResponse {
		if (self::recognizeIsInstalled()) {
			// Obtain API Key
            if (null !== $this->apiKeyManager) {
                try {
                    $apiKey = $this->apiKeyManager->generateApiKey();
					return new JSONResponse([
						'apiKey' => $apiKey
					], Http::STATUS_OK);
                } catch (\JsonException $e) {
                    $this->logger->error('Failed to generate recognize api key', ['exception' => $e]);
                }
            }
			return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		} else {
			return new JSONResponse([], Http::STATUS_NOT_IMPLEMENTED);
		}
	}

	/**
	 * Copied from Memories/Util.php
	 */
    public static function recognizeIsInstalled(): bool {
        $appManager = \OC::$server->get(IAppManager::class);

        if (!$appManager->isEnabledForUser('recognize')) {
            return false;
        }

        $v = $appManager->getAppVersion('recognize');

        return version_compare($v, '3.8.0', '>=');
    }
}

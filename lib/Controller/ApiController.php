<?php

declare(strict_types=1);

namespace OCA\NcPhotosServer\Controller;

use OCA\NcPhotosServer\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
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
	#[ApiRoute(verb: 'GET', url: '/api/recognize_api_key')]
	public function recognizeApiKey(): Response {
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

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/health')]
	public function health(): Response {
		return new JSONResponse([
			"version" => 10000,
			"recognizeOk" => self::recognizeIsInstalled(),
		], Http::STATUS_OK);
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

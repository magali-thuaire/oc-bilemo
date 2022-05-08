<?php /** @noinspection ALL */

namespace App\Tests\Utils;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Throwable;
use Zenstruck\Foundry\Proxy;

class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected KernelBrowser $authorizedClient;
    private $output;
    private $responseAsserter;
    private $formatterHelper;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->purgeDatabase();
    }

    protected function setAuthorizedClient(): Proxy {

        $user = $this->createApiClient('authorized@bilemo.fr');

        $token = $this->getService('lexik_jwt_authentication.encoder')
                      ->encode(['email' => $user->getEmail()]);
        $autorization = 'Bearer '. $token;

        $this->client->setServerParameter('HTTP_AUTHORIZATION', $autorization);

        return $user;
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
//        if (self::$history && $lastResponse = $this->client->getResponse()) {
//            $this->printDebug('');
//            $this->printDebug('<error>Failure!</error> when making the following request:');
//            $this->printDebug('');
//            $response = $this->client->getResponse();
//            $this->debugResponse($response);
//        }
//        throw new ExpectationFailedException();
        throw $t;
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->getService('doctrine')->getManager());
        $purger->purge();
    }

    protected function getService($id): ?object
    {
        return self::getContainer()->get($id);
    }

    protected function debugResponse(Response $response)
    {
        $this->printDebug($response);
        $body = (string) $response->getContent();

        $contentType = $response->headers->get('content-type');
        if ($contentType == 'application/json' || str_contains($contentType, '+json')) {
            $data = json_decode($body);
            if ($data === null) {
                // invalid JSON!
                return $this->printDebug($body);
            }
            // valid JSON, print it pretty
            return $this->printDebug(json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    protected function printDebug($string)
    {
        if (null === $this->output) {
            $this->output = new ConsoleOutput();
        }
        $this->output->writeln($string);
    }

    protected function printErrorBlock($string)
    {
        if ($this->formatterHelper === null) {
            $this->formatterHelper = new FormatterHelper();
        }
        $output = $this->formatterHelper->formatBlock($string, 'bg=red;fg=white', true);

        $this->printDebug($output);
    }

    protected function asserter(): ResponseAsserter
    {
        if (null === $this->responseAsserter) {
            $this->responseAsserter = new ResponseAsserter();
        }

        return $this->responseAsserter;
    }

    protected function createApiClient(string $email = 'test@bilemo.fr'): Proxy|User
    {
        return UserFactory::new()
                  ->withAttributes([
                      'email' => $email,
                      'password' => 'bilemo'
                  ])
                  ->createdNow()
                  ->promoteRole('ROLE_CLIENT')
                  ->create();
    }
}

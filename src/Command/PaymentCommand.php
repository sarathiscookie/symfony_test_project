<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\PaymentService;

class PaymentCommand extends Command
{
    protected static $defaultName = 'app:payment';
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Configures the command with arguments and description.
     */
    protected function configure()
    {
        $this
            ->setDescription('Process payment through a specified provider.')
            ->addArgument('provider', InputArgument::REQUIRED, 'Payment provider (aci or shift4)')
            ->addArgument('amount', InputArgument::REQUIRED, 'Payment amount')
            ->addArgument('currency', InputArgument::REQUIRED, 'Payment currency')
            ->addArgument('cardNumber', InputArgument::REQUIRED, 'Card number')
            ->addArgument('expMonth', InputArgument::REQUIRED, 'Card expiration month')
            ->addArgument('expYear', InputArgument::REQUIRED, 'Card expiration year')
            ->addArgument('cvv', InputArgument::REQUIRED, 'Card CVV');
    }

    /**
     * Executes the payment processing command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = $input->getArgument('provider');

        try {
            // Validate the provider
            if (!in_array($provider, ['shift4', 'aci'], true)) {
                throw new \InvalidArgumentException("Unsupported provider: $provider");
            }

            // Build parameters based on provider
            $params = $this->buildParams($provider, $input);

            // Process payment
            $response = $this->paymentService->processPayment($provider, $params);

            // Output response
            $output->writeln(json_encode($response, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Invalid Input: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Builds the parameters array based on the provider.
     *
     * @param string $provider
     * @param InputInterface $input
     * @return array
     */
    private function buildParams(string $provider, InputInterface $input): array
    {
        // Shared parameters for all providers
        $commonParams = [
            'amount' => $input->getArgument('amount'),
            'currency' => $input->getArgument('currency'),
        ];

        // Provider-specific parameters
        $providerParams = [];

        switch ($provider) {
            case 'shift4':
                $providerParams = [
                    'number' => $input->getArgument('cardNumber'),
                    'expMonth' => $input->getArgument('expMonth'),
                    'expYear' => $input->getArgument('expYear'),
                    'cvc' => $input->getArgument('cvv'),
                ];
                break;

            case 'aci':
                $providerParams = [
                    'card.number' => $input->getArgument('cardNumber'),
                    'card.expiryMonth' => $input->getArgument('expMonth'),
                    'card.expiryYear' => $input->getArgument('expYear'),
                    'card.cvv' => $input->getArgument('cvv'),
                ];
                break;

            default:
                throw new \InvalidArgumentException("Unsupported provider: $provider");
        }

        // Merge common parameters with provider-specific parameters
        return array_merge($commonParams, $providerParams);
    }
}

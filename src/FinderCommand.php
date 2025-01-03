<?php

namespace PhpUnitFinder;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A symfony command for finding PHPUnit files (compatible with PHPUnit 8.x).
 */
class FinderCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            'config-file',
            'c',
            InputOption::VALUE_OPTIONAL,
            "The phpunit.xml config file to use.",
            getcwd() . '/phpunit.xml'
        );
        $this->addOption(
            'bootstrap-file',
            'b',
            InputOption::VALUE_OPTIONAL,
            "The tests bootstrap file.",
            getcwd() . '/tests/bootstrap.php'
        );
        $this->addArgument(
            'test-suite',
            InputArgument::IS_ARRAY,
            "The test suites to scan."
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Read CLI options / arguments
        $configFile = $input->getOption('config-file');
        $bootstrap  = $input->getOption('bootstrap-file');
        $testSuites = $input->getArgument('test-suite');

        // Include your bootstrap if needed
        include_once $bootstrap;

        // Load the PHPUnit XML configuration
        $config    = Configuration::getInstance($configFile);
        $mainSuite = $config->getTestSuiteConfiguration();

        $testFilenames = [];

        // Loop through sub-suites in the main config
        foreach ($mainSuite->tests() as $suite) {
            // Only proceed if this is a proper TestSuite
            if (!$suite instanceof TestSuite) {
                continue;
            }

            // If specific suite names were given, skip others
            if (!empty($testSuites) && !in_array($suite->getName(), $testSuites, true)) {
                continue;
            }

            // Recursively walk through all tests in this suite
            foreach (new \RecursiveIteratorIterator($suite) as $test) {
                if ($test instanceof TestCase) {
                    $reflection     = new \ReflectionClass($test);
                    $testFilenames[] = $reflection->getFileName();
                }
            }
        }

        // Print unique files
        $testFilenames = array_unique($testFilenames);
        foreach ($testFilenames as $file) {
            $output->writeln($file);
        }

        // Return 0 to indicate success
        return 0;
    }
}

<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Create a site.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CreateSiteCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sonata:page:create-site');

        $this->addOption('no-confirmation', null, InputOption::VALUE_OPTIONAL, 'Ask confirmation before generating the site', false);

        $this->addOption('enabled', null, InputOption::VALUE_OPTIONAL, 'Site.enabled', false);
        $this->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Site.name', null);
        $this->addOption('relativePath', null, InputOption::VALUE_OPTIONAL, 'Site.relativePath', null);
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Site.host', null);
        $this->addOption('enabledFrom', null, InputOption::VALUE_OPTIONAL, 'Site.enabledFrom', null);
        $this->addOption('enabledTo', null, InputOption::VALUE_OPTIONAL, 'Site.enabledTo', null);
        $this->addOption('default', null, InputOption::VALUE_OPTIONAL, 'Site.default', null);
        $this->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Site.locale', null);

        $this->addOption('base-command', null, InputOption::VALUE_OPTIONAL, 'Site id', 'php app/console');

        $this->setDescription('Create a site');

        $this->setHelp(<<<'EOT'
The <info>sonata:page:create-site</info> command create a new site entity.

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /*
         * NEXT_MAJOR: remove if statement and use the question helper only (true value of if) when dropping sf < 2.5
         */
        if ($this->getHelperSet()->has('question')) {
            $helper = $this->getHelperSet()->get('question');
        } else {
            $helper = $this->getHelperSet()->get('dialog');
        }

        $values = [
            'name' => null,
            'host' => null,
            'relativePath' => null,
            'enabled' => null,
            'enabledFrom' => null,
            'enabledTo' => null,
            'default' => null,
            'locale' => null,
        ];

        foreach ($values as $name => $value) {
            $values[$name] = $input->getOption($name);

            while ($values[$name] == null) {
                /*
                 * NEXT_MAJOR: remove if statement and use the question helper only (true value of if) when dropping sf < 2.5
                 */
                if ($this->getHelperSet()->has('question')) {
                    $question = new Question(
                        sprintf('Please define a value for <info>Site.%s</info> : ', $name)
                    );
                    $values[$name] = $helper->ask(
                        $input,
                        $output,
                        $question
                    );
                } else {
                    $values[$name] = $helper->ask(
                        $output,
                        sprintf('Please define a value for <info>Site.%s</info> : ', $name)
                    );
                }
            }
        }

        // create the object
        $site = $this->getSiteManager()->create();

        $site->setName($values['name']);

        $site->setRelativePath($values['relativePath'] == '/' ? '' : $values['relativePath']);

        $site->setHost($values['host']);
        $site->setEnabledFrom($values['enabledFrom'] == '-' ? null : new \DateTime($values['enabledFrom']));
        $site->setEnabledTo($values['enabledTo'] == '-' ? null : new \DateTime($values['enabledTo']));
        $site->setIsDefault(in_array($values['default'], ['true', 1, '1']));
        $site->setLocale($values['locale'] == '-' ? null : $values['locale']);
        $site->setEnabled(in_array($values['enabled'], ['true', 1, '1']));

        $info_enabledFrom = $site->getEnabledFrom() instanceof \DateTime ? $site->getEnabledFrom()->format('r') : 'ALWAYS';
        $info_enabledTo = $site->getEnabledTo() instanceof \DateTime ? $site->getEnabledTo()->format('r') : 'ALWAYS';

        $output->writeln(<<<INFO

Creating website with the following information :
  <info>name</info> : {$site->getName()}
  <info>site</info> : http(s)://{$site->getHost()}{$site->getRelativePath()}
  <info>enabled</info> :  <info>from</info> {$info_enabledFrom} => <info>to</info> {$info_enabledTo}

INFO
        );

        /*
         * NEXT_MAJOR: remove if statement and use the question helper only (true value of if) when dropping sf < 2.5
         */
        if ($this->getHelperSet()->has('question')) {
            $question = new ConfirmationQuestion(
                'Confirm site creation (Y/N)',
                false,
                '/^(y)/i'
            );
            $confirmation = $helper->ask($input, $output, $question);
        } else {
            $confirmation = $input->getOption('no-confirmation') || $helper->askConfirmation(
                $output,
                'Confirm site creation?',
                false
            );
        }
        if ($confirmation) {
            $this->getSiteManager()->save($site);

            $output->writeln([
                '',
                '<info>Site created !</info>',
                '',
                'You can now create the related pages and snapshots by running the followings commands:',
                sprintf('  php app/console sonata:page:update-core-routes --site=%s', $site->getId()),
                sprintf('  php app/console sonata:page:create-snapshots --site=%s', $site->getId()),
            ]);
        } else {
            $output->writeln('<error>Site creation cancelled !</error>');
        }
    }
}

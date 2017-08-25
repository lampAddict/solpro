<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ClearLotsDBDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('data:clear')

            // the short description shown while running "php app/console list"
            ->setDescription('Clear lot prices data from db.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Removes lots, routes, orders and bets from database.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->createQuery('delete from AppBundle\Entity\Bet b where b.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Order o where o.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Route r where r.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Lot l where l.id > 0')->execute();

        $output->writeln('Lots data removed from db.');

        $this->getContainer()->get('snc_redis.default')->flushall();

        $output->writeln('Cached lots data cleared.');
    }
}
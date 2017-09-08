<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setHelp("Removes lots, routes, orders, bets, drivers, vehicles from database.")
            ->addArgument('clearReferences', InputArgument::OPTIONAL, 'Clear references tables also')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->createQuery('delete from AppBundle\Entity\Bet b where b.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Order o where o.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Lot l where l.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Route r where r.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Transport t where t.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Driver d where d.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Exchange e where e.id > 0')->execute();
        $em->createQuery('delete from AppBundle\Entity\Filter f where f.id > 0')->execute();

        $output->writeln('Orders, bets, lots, vehicles, drivers, routes, exchanges, filters data removed from db.');

        $clearReferences = $input->getArgument('clearReferences');
        if( $clearReferences === 'true' ){

            $em->createQuery('delete from AppBundle\Entity\RefCarrierUser rcu where rcu.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefCarrier rc where rc.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefPartner rp where rp.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefPassport rp where rp.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefRegion rr where rr.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefVehicleType rvt where rvt.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefVehicleCarryingType rvct where rvct.id > 0')->execute();
            $em->createQuery('delete from AppBundle\Entity\RefRouteStatus rrs where rrs.id > 0')->execute();

            $em->createQuery('update AppBundle\Entity\RefLotStatus rls set rls.id1C=\'\', rls.name=\'\'')->execute();

            $output->writeln('References data cleared from db.');
        }

        $this->getContainer()->get('snc_redis.default')->flushall();

        $output->writeln('Cached lots data cleared.');
    }
}
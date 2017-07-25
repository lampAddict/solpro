<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class AuctionAutoEndCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('auction:close')

            // the short description shown while running "php app/console list"
            ->setDescription('Automatically closes all ended auctions')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command checks list of running auctions and closes all of ended auctions")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Auction close procedure started.. ');
        $auctionManager = $this->getContainer()->get('app.closeauction');
        if( $auctionManager->closeAuctionService() ){
            $output->writeln('Done.');
        }
        else{
            $output->writeln('Something gone wrong.');
        }
    }
}
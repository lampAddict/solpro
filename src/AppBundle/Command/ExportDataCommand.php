<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ExportDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('exportdata')

            // the short description shown while running "php app/console list"
            ->setDescription('Export data to 1C.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command composes auction results into xml data file and uploads it to ftp")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Export started..');
        $export1CDataManager = $this->getContainer()->get('app.export1cdata');
        if( $export1CDataManager->exportData(26904, 1) ){
            $output->writeln('Upload xml to ftp');
            $conn_id = ftp_connect('10.32.2.19') or die("Couldn't establish connection to ftp server");
            if( !ftp_login($conn_id, 'ftp_1c', 'cURz46mGDs') )die("Couldn't login to ftp server");
            if( ftp_put($conn_id, 'messageFromPortal.xml', 'data/messageFromPortal.xml', FTP_BINARY) ){
                rename('data/messageFromPortal.xml', 'data/data_exported/data_'.date('H_i_s__d_m_Y', time()).'.xml');
            }
            ftp_close($conn_id);
        }
    }
}
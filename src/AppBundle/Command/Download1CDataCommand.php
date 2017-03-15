<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Download1CDataCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('data:download')

            // the short description shown while running "php app/console list"
            ->setDescription('Download data from 1C.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to download auction lots data onto portal from 1C")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Connecting to ftp..');
        //ftp.solpro.ru
        $conn_id = ftp_connect('10.32.2.19') or die("Couldn't establish connection to ftp server");
        if( !ftp_login($conn_id, 'ftp_1c', 'cURz46mGDs') )die("Couldn't login to ftp server");
        //ftp_pasv($conn_id, TRUE);
        //ftp_set_option($conn_id, FTP_TIMEOUT_SEC, 360);
        if( ftp_get($conn_id, 'data/data.xml', 'MessageFrom1C.xml', FTP_BINARY) )$output->writeln('XML file downloaded successfully');
        ftp_close($conn_id);
    }
}
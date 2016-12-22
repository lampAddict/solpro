<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DomCrawler\Crawler;

class Import1CDataCommand extends Command
{
    protected $refs;

    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('import1cdata')

            // the short description shown while running "php app/console list"
            ->setDescription('Import data from 1C.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to get up to date data from 1C")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Import data started..');

        $data = new Crawler(file_get_contents('web/data.xml'));

        if( $data ){
            $refs = $data->filter('MessageFrom1C > references')->children();
            if( !empty($refs) ){
                foreach( $refs as $ref ){
                    $items = $data->filter('MessageFrom1C > references > '.$ref->nodeName)->children();
                    if( !empty($items) ){
                        $count = 1;
                        foreach( $items as $item ){
                            $itemsData = $data->filter('MessageFrom1C > references > '.$ref->nodeName.' > '.$item->nodeName.':nth-child('.$count.')')->children();
                            if( !empty($itemsData) ){
                                $el = [];
                                $key = '';
                                foreach ($itemsData as $itemData){
                                    if( $itemData->nodeName == 'id' ){
                                        $key = $itemData->nodeValue;
                                    }
                                    $el[ $itemData->nodeName ] = $itemData->nodeValue;
                                }

                                if( $key != '' ){
                                    $this->refs[$item->nodeName][$key] = $el;
                                }

                                $count++;
                            }
                        }
                    }
                }

                var_dump($this->refs);
                die;
            }
        }
    }
}
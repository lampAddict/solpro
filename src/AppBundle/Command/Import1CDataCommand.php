<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\DomCrawler\Crawler;

class Import1CDataCommand extends ContainerAwareCommand
{
    protected $refs;
    protected $routes;
    protected $orders;
    protected $lots;

    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('import1cdata')

            // the short description shown while running "php app/console list"
            ->setDescription('Import data from 1C.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to import routes data onto portal from 1C")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Import data started..');

        $data = new Crawler(file_get_contents('web/data.xml'));
        if( $data ){
            //Parsing references block
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
            }

            //Parsing routes block
            $routes = $data->filter('MessageFrom1C > routes')->children();
            if( !empty($routes) ){
                $count = 1;
                foreach ($routes as $route){
                    $routeData = $data->filter('MessageFrom1C > routes > route:nth-child('.$count.')')->children();

                    $el = [];
                    $key = '';
                    foreach ($routeData as $routeDataItem){

                        $nodeValue = $routeDataItem->nodeValue;

                        if( $routeDataItem->nodeName == 'id' ){
                            $key = $routeDataItem->nodeValue;
                        }

                        //Parsing orders block
                        if( $routeDataItem->nodeName == 'orders' ){

                            $orders = $data->filter('MessageFrom1C > routes > route:nth-child('.$count.') > orders')->children();
                            if( !empty($orders) ){
                                $nodeValue = [];
                                $count1 = 1;
                                foreach ($orders as $order){
                                    $ordersData = $data->filter('MessageFrom1C > routes > route:nth-child('.$count.') > orders > order:nth-child('.$count1.')')->children();
                                    $elOrder = [];
                                    $keyOrder = '';
                                    foreach ($ordersData as $ordersDataItem){
                                        if( $ordersDataItem->nodeName == 'id' ){
                                            $keyOrder = $ordersDataItem->nodeValue;
                                        }

                                        $elOrder[ $ordersDataItem->nodeName ] = $ordersDataItem->nodeValue;
                                    }

                                    if(    $keyOrder != ''
                                        && !empty($elOrder)
                                    ){
                                        $this->orders[ $keyOrder ] = $elOrder;
                                    }

                                    $count1++;
                                    $nodeValue[] = $elOrder;
                                }
                            }
                        }

                        $el[ $routeDataItem->nodeName ] = $nodeValue;
                    }

                    if(    $key != ''
                        && !empty($el)
                    ){
                        $this->routes[ $key ] = $el;
                    }

                    $count++;
                }
            }

            //Parsing lots block
            $lots = $data->filter('MessageFrom1C > lots')->children();
            if( !empty($lots) ){
                $count = 1;
                foreach ($lots as $lot){
                    $lotData = $data->filter('MessageFrom1C > lots > lot:nth-child('.$count.')')->children();

                    $el = [];
                    $key = '';
                    foreach ($lotData as $lotDataItem){
                        if( $lotDataItem->nodeName == 'id' ){
                            $key = $lotDataItem->nodeValue;
                        }

                        $el[ $lotDataItem->nodeName ] = $lotDataItem->nodeValue;;
                    }

                    if(    $key != ''
                        && !empty($el)
                    ){
                        $this->lots[ $key ] = $el;
                    }

                    $count++;
                }
            }

            $data = [
                 'lots' => $this->lots
                ,'routes' => $this->routes
                ,'ref' => $this->refs
            ];
            
            $import1CDataManager = $this->getContainer()->get('app.import1cdata');
            $import1CDataManager->import1CData($data);

        }
    }
}
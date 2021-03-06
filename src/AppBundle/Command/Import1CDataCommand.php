<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

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
            ->setName('data:import')

            // the short description shown while running "php app/console list"
            ->setDescription('Import data from 1C.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to import auction lots data onto portal from 1C")
            ->addArgument('downloadFromFTP', InputArgument::OPTIONAL, 'Download data from ftp ?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $downloadFromFTP = $input->getArgument('downloadFromFTP');
        if( $downloadFromFTP === 'true' ){
            $output->writeln('Connecting to ftp..');
            $conn_id = ftp_connect('10.32.2.19') or die("Couldn't establish connection to ftp server");//ftp.solpro.ru
            if( !ftp_login($conn_id, 'ftp_1c', 'cURz46mGDs') )die("Couldn't login to ftp server");

            if( file_exists('data/data.xml') ){
                $output->writeln('Data file data.xml exist, some data could be lost, download procedure aborted');
                return;
            }

            if( ftp_get($conn_id, 'data/data.xml', 'MessageFrom1C.xml', FTP_BINARY) ){
                $output->writeln('XML file downloaded successfully');
                ftp_delete($conn_id, 'MessageFrom1C.xml');
            }
            ftp_close($conn_id);
        }
        $output->writeln('Import data started..');

        //if data file wasn't successfully downloaded skip import procedure, do just export
        if( !file_exists('data/data.xml') ){
            //Do export
            $this->doExportRoutine($output, 0, 0);

            return;
        }
        
        //Prepare data
        $data = new Crawler();
        $data->addXmlContent(file_get_contents('data/data.xml'));

        if( $data ){
            //Send number
            $sendNum = $data->filter('MessageFrom1C')->attr('sendNumber');
            $recNum = $data->filter('MessageFrom1C')->attr('recNumber');
            
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
                $output->writeln('References parsed');
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
                $output->writeln('Routes parsed');
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
                $output->writeln('Lots parsed');
            }

            $data = [
                 'lots' => $this->lots
                ,'routes' => $this->routes
                ,'ref' => $this->refs
                ,'sendNum' => $sendNum
                ,'recNum' => $recNum
            ];
            
            //Do import
            $import1CDataManager = $this->getContainer()->get('app.import1cdata');
            if( $import1CDataManager->import1CData($data) ){
                //clear all cached lots data
                //$this->getContainer()->get('snc_redis.default')->flushall();

                //clear cached lots current prices data
                //$this->getContainer()->get('snc_redis.default')->del("lcp");

                rename('data/data.xml', 'data/data_imported/data_'.date('H_i_s__d_m_Y', time()).'.xml');
            }
            else{
                //if import failed delete source file
                //unlink('data/data.xml');
            }
            
            //Do export
            $this->doExportRoutine($output, $sendNum, $recNum);
        }
    }

    protected function doExportRoutine($output, $sendNum, $recNum){
        $output->writeln('Export');
        $export1CDataManager = $this->getContainer()->get('app.export1cdata');
        if( $export1CDataManager->exportData($sendNum, $recNum) ){
            $output->writeln('Upload xml to ftp');
            $conn_id = ftp_connect('10.32.2.19') or die("Couldn't establish connection to ftp server");
            if( !ftp_login($conn_id, 'ftp_1c', 'cURz46mGDs') )die("Couldn't login to ftp server");

            $files = ftp_rawlist($conn_id, '');
            if( !empty($files) ){
                foreach( $files as $file ){
                    if( strpos($file, 'messageFromPortal.xml') !== false ){
                        $output->writeln('messageFromPortal.xml already exists, wait till it will be loaded by 1C');
                        return;
                    }
                }
            }

            if( ftp_put($conn_id, 'messageFromPortal.xml', 'data/messageFromPortal.xml', FTP_BINARY) ){
                rename('data/messageFromPortal.xml', 'data/data_exported/data_'.date('H_i_s__d_m_Y', time()).'.xml');
            }
            ftp_close($conn_id);
        }
    }
}
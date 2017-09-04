<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Admin controller.
 *
 * @Route("admin")
 */
class AdminController extends Controller
{
    /**
     * Lists all active auctions
     *
     * @Route("/", name="admin")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        //get lots data
        $sql = 'SELECT l.*, u.username as user_name, r.code as code, r.region_from, r.region_to FROM lot l LEFT JOIN bet b ON b.lot_id = l.id LEFT JOIN fos_user u ON b.user_id = u.id LEFT JOIN route r ON r.id = l.route_id WHERE l.price >= IFNULL(b.value, 0) GROUP BY l.id ORDER BY l.start_date DESC, l.auction_status DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_lots = $stmt->fetchAll();

        //get routes ids
        $routesIds = [];
        if( !empty($_lots) ){
            foreach( $_lots as $indx=>$lot ){
                $_lots[ $indx ]['start_date']  = new \DateTime( $lot['start_date'] );
            }
        }
        else{
            $_lots = [];
        }

        return $this->render('admin/adminPage.html.twig', array(
             'lots' => $_lots
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
        ));
    }

    /**
     * Lists all site users
     *
     * @Route("/users", name="users")
     * @Method("GET")
     */
    public function showUsersAction(){

        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        $users = $em
            ->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->getQuery()
            ->getResult();

        $_users = [];
        foreach ($users as $user){
            /* @var $user \AppBundle\Entity\User */
            $_users[] = [
                             'id'=>$user->getId()
                            ,'login'=>$user->getEmail()
                            ,'name'=>$user->getUsername()
                            ,'roles'=>$user->getRoles()
                            ,'active'=>($user->isEnabled()?1:0)
            ];
        }

        $_roles = $this->container->getParameter('security.role_hierarchy.roles');

        return $this->render('admin/adminUsersPage.html.twig', array(
             'users' => $_users
            ,'roles' => $_roles['ROLE_ADMIN']
            ,'captionRoles' => ['ROLE_ADMIN'=>'Администратор', 'ROLE_USER'=>'Пользователь', 'ROLE_AUCTION'=>'Торги', 'ROLE_ROUTES'=>'Рейсы', ''=>'']
        ));
    }


    /**
     * Set/unset user roles.
     *
     * @Route("/setUserRole", name="setUserRole")
     * @Method("POST")
     */
    public function setUserRoleAction(Request $request){

        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        $user = $em
                ->getRepository('AppBundle:User')
                ->createQueryBuilder('u')
                ->where('u.id = :uid')
                ->setParameter('uid', intval($request->request->get('uid')))
                ->getQuery()
                ->getResult();

        $err = '';

        if( !empty($user) ){
            $user = $user[0];
            $addRole = $request->request->get('addRole');
            $removeRole = $request->request->get('removeRole');
            /* @var $user \AppBundle\Entity\User */
            $updateUser = false;

            $roles = [ 'ROLE_USER', 'ROLE_AUCTION', 'ROLE_ROUTES' ];
            if(
                   $addRole != ''
                && in_array($addRole, $roles)
            ){
                if( !$user->hasRole($addRole) ){
                    $user->addRole($addRole);
                    $updateUser = true;
                }
                else{
                    $err .= "Пользователю уже назначена эта роль.\n";
                }
            }

            if(
                   $removeRole != ''
                && in_array($removeRole, $roles)
            ){
                if( $user->hasRole($removeRole) ){
                    $user->removeRole($removeRole);
                    $updateUser = true;
                }
                else{
                    $err .= "У пользователя нет запрашиваемой на удаление роли.\n";
                }
            }

            if( $updateUser ){
                $userManager = $this->container->get('fos_user.user_manager');
                $userManager->updateUser($user);
                $em->flush();

                return new JsonResponse(['result'=>true, 'msg'=>$err]);
            }

        }
        else{
            return new JsonResponse(['result'=>false, 'msg'=>'Пользователь не найден.']);
        }

        return new JsonResponse(['result'=>false, 'msg'=>$err]);
    }

    /**
     * Block/unblock user profile depends on its current state
     *
     * @Route("/setUserBlock", name="setUserBlock")
     * @Method("POST")
     */
    public function setUserBlockAction(Request $request){

        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        $user = $em
                ->getRepository('AppBundle:User')
                ->createQueryBuilder('u')
                ->where('u.id = :uid')
                ->setParameter('uid', intval($request->request->get('uid')))
                ->getQuery()
                ->getResult();

        if( !empty($user) ){
            /* @var $user \AppBundle\Entity\User */
            $user = $user[0];
            if( $user->hasRole('ROLE_ADMIN') === false ){
                $user->setEnabled(!$user->isEnabled());
                $em->flush();

                return new JsonResponse(['result'=>true]);
            }
        }

        return new JsonResponse(['result'=>false]);
    }


    /**
     * Finds and displays all lot bids.
     *
     * @Route("/lot/{id}", name="lot_bids_show")
     * @Method("GET")
     */
    public function showLotBidsAction($id)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $_bids = [];
        //get lots data
        $sql = 'SELECT b.value, b.created_at, u.username as user_name FROM bet b LEFT JOIN fos_user u ON b.user_id = u.id WHERE b.lot_id="'.intval($id).'" ORDER BY b.created_at DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_bids = $stmt->fetchAll();

        if( !empty($_bids) ){
            foreach( $_bids as $indx=>$bid ){
                $_bids[ $indx ]['created_at']  = new \DateTime( $bid['created_at'] );
            }
        }

        return $this->render('admin/adminLotPage.html.twig', array(
             'bids'=>$_bids
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
        ));
    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class FollowController extends AbstractController
{

    /**
     * Follows or unfollows the given user, for the user with the id associated
     * with the 'userId' object in the request data.
     * @param Request $req Post request object with a body containing a 'userId' and 'setFollowTo'
     * @param EntityManagerInterface $em
     * @param int $userId The ID of the User to be followed/unfollowed
     * @return JsonResponse 204 on success
     */
    #[Route('/user/follow/{userId}', name: 'follow_user')]
    public function setFollowing(Request $req, EntityManagerInterface $em, int $userId): JsonResponse
    {
        // Finding the user being followed/unfollowed
        $userRepo = $em->getRepository(User::class);
        $userBeingFollowed = $userRepo->findOneBy(['id' => $userId]);

        // The user does not exist
        if (!$userBeingFollowed) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);
        }

        // Getting the body data
        $data = json_decode($req->getContent(), true);

        // No follower given or the follower is the followee
        if (!$data['userId'] || $data['userId'] == $userId)
        {
            return new JsonResponse(['error' => 'Users cannot follow themselves'], Response::HTTP_BAD_REQUEST);
        }

        // Finding the follower
        $FollowingUser = $userRepo->findOneBy(['id' => $data['userId']]);

        // Follower does not exist
        if (!$FollowingUser) {
            return new JsonResponse(['error' => 'Following user not found'], Response::HTTP_BAD_REQUEST);
        }

        // Getting the follow status (true for follow)
        $status = $data["setFollowTo"];

        // Following
        if ($status)
        {
            $FollowingUser->followUser($userBeingFollowed);
            $userBeingFollowed->addToFollowersList($FollowingUser);
        }
        else
        {
            // Unfollowing
            $FollowingUser->unfollowUser($userBeingFollowed);
            $userBeingFollowed->removeFromFollowersList($FollowingUser);
        }

        $em->persist($FollowingUser);
        $em->persist($userBeingFollowed);

        $em->flush();

        return new JsonResponse(["Message" => "Success"], Response::HTTP_NO_CONTENT);
    }


    /**
     * Checks whether the given user is being followed by the user associated
     * with the query variable 'userId'.
     * @param Request $req GET Request
     * @param EntityManagerInterface $em
     * @param int $userId The ID of the User to check the followers of
     * @return JsonResponse 200 JSON with 'Following' variable of true or false
     */
    #[Route('/user/follow/{userId}', name: 'follow_user_status')]
    public function getFollowStatus(Request $req, EntityManagerInterface $em, int $userId): JsonResponse
    {
        // Finding the user being followed
        $userRepo = $em->getRepository(User::class);
        $userBeingFollowed = $userRepo->findOneBy(['id' => $userId]);

        // The user does not exist
        if (!$userBeingFollowed) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);
        }

        // Getting the querying user
        $FollowingUserID = $req->query->get('userId');

        // No one is querying or querying themselves
        if (!$FollowingUserID || $FollowingUserID == $userId)
        {
            return new JsonResponse(['error' => 'Users cannot follow themselves'], Response::HTTP_BAD_REQUEST);
        }

        // Finding the following user
        $FollowingUser = $userRepo->findOneBy(['id' => $FollowingUserID]);

        // user does not exist
        if (!$FollowingUser) {
            return new JsonResponse(['error' => 'Following user not found'], Response::HTTP_BAD_REQUEST);
        }

        $followers = $userBeingFollowed->getFollowers();

        // Searching for the follower among the following
        foreach ($followers as $follower)
        {
            if ($follower->getId() == $FollowingUser->getId())
            {
                // The follower is following
                return new JsonResponse(["Following" => true], Response::HTTP_OK);
            }
        }

        // Could not find the follower, so they are not following
        return new JsonResponse(["Following" => false], Response::HTTP_OK);
    }

}

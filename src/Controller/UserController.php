<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;


class UserController extends AbstractController
{
    /**
     * @var UserRepository
    */
    private $userRepository;

    /**
     * @var Validation
    */
    private $validator;

    /**
     * @param UserRepository $userRepository
     * @return void
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->validator = Validation::createValidator();
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $violations = $this->validator->validate($data, $this->getConstraints());

        if ($violations->count() > 0) {
            return new JsonResponse(
                ["error" => (string)$violations],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        try {
            $this->userRepository->saveUser($data);
        } catch (\Exception $e) {
            return new JsonResponse(
                ["error" => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(['status' => 'User registered!'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/users", name="users", methods={"GET"})
     * @return JsonResponse
     */
    public function users(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = $user->toArray();
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * @Route("/users/{id}", name="update_user", methods={"PUT"})
     * @param int $id
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function update($id, Request $request, UserPasswordEncoderInterface $encoder): JsonResponse
    {
        if ($id != $this->getUser()->getId()) {
            return new JsonResponse(
                ["error" => 'Editing is available only to yourself!'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $user = $this->userRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);

        empty($data['firstName']) ? true : $user->setFirstName($data['firstName']);
        empty($data['lastName']) ? true : $user->setLastName($data['lastName']);
        empty($data['email']) ? true : $user->setEmail($data['email']);
        empty($data['phone']) ? true : $user->setPhone($data['phone']);
        empty($data['password']) ? true : $user->setPassword(
            $encoder->encodePassword($user, $data['password'])
        );

        try {
            $updatedUser = $this->userRepository->updateUser($user);
        } catch (\Exception $e) {
            return new JsonResponse(
                ["error" => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse($updatedUser->toArray(), Response::HTTP_OK);
    }

    /**
     * @Route("/users/{id}", name="delete_user", methods={"DELETE"})
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $id]);

        $this->denyAccessUnlessGranted(
            'ROLE_ADMIN',
            $user,
            'Unable to access this page!'
        );

        try {
            $this->userRepository->removeUser($user);
        } catch (\Exception $e) {
            return new JsonResponse(
                ["error" => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(['status' => 'User deleted'], Response::HTTP_NO_CONTENT);
    }

    /**
     * @return Collection
     */
    private function getConstraints(): Collection
    {
        return new Assert\Collection([
            'firstName' => new Assert\Length(['min' => 2]),
            'lastName' => new Assert\Length(['min' => 2]),
            'email' => new Assert\Email(),
            'phone' => [new Assert\Regex('/^(\+)(\d)+/i'), new Assert\Length(['min' => 13])],
            'password' => new Assert\Length(['min' => 2]),
        ]);
    }
}

<?php

namespace App\Service;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class FormService
{
    private SerializerInterface $serializer;
    private FormFactoryInterface $formFactory;

    public function __construct(
        SerializerInterface $serializer,
        FormFactoryInterface $formFactory
    ) {
        $this->serializer = $serializer;
        $this->formFactory = $formFactory;
    }

    public function processForm(
        Request $request,
        string $entityType,
        string $classType,
        array $context = []
    ): FormInterface {

        $object = $this->serializer->deserialize($request->getContent(), $entityType, 'json', $context);
        $data = $this->serializer->normalize($object, 'json', $context);
        $form = $this->formFactory->create($classType, $object);

        if (empty($data)) {
            $apiProblem = new ApiProblem(
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT
            );
            throw new ApiProblemException($apiProblem);
        }

        $form->submit($data);

        return $form;
    }

    public function throwApiProblemValidationException(FormInterface $form, array $reverse = []): void
    {
        $errors = $this->getErrors($form, $reverse);
        $apiProblem = new ApiProblem(
            Response::HTTP_BAD_REQUEST,
            ApiProblem::TYPE_VALIDATION_ERROR
        );
        $apiProblem->set('errors', $errors);

        throw new ApiProblemException($apiProblem);
    }

    private function throwApiProblemUnprocessableEntityException(string $error)
    {
        $apiProblem = new ApiProblem(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ApiProblem::TYPE_UNPROCESSABLE_ENTITY
        );
        $apiProblem->set('errors', $error);

        throw new ApiProblemException($apiProblem);
    }

    public function getErrors(FormInterface $form, array $reverse = []): ?array
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            // UniqueEntity
            if ($error->getCause()?->getConstraint()?->validatedBy() === 'doctrine.orm.validator.unique') {
                $this->throwApiProblemUnprocessableEntityException($error->getMessage());
            };

            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrors($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        if (!empty($reverse) && array_key_exists($term = array_key_first($reverse), $errors)) {
            $errors[$reverse[$term]] = $errors[$term];
            unset($errors[$term]);
        }

        return $errors;
    }
}

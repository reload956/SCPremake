<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\Type\TagsInputType;

class ProductType extends AbstractType
{
    private $slugger;

    // Form types are services, so you can inject other services in them if needed
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name',TextType::class)
            ->add('summary',TextType::class)
            ->add('price',MoneyType::class)
            ->add('description',TextareaType::class)
            ->add('summary',TextType::class)
            ->add('image_form', FileType::class, ['required' => true])
            ->add('quantity',IntegerType::class)
            ->add('category',EntityType::class, ['class'=>'App\Entity\Category'])
            ->add('tags', TagsInputType::class, [
                'label' => 'label.tags',
                'required' => false,
            ])
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                /** @var Product */
                $product = $event->getData();
                if (null !== $ProductTitle = $product->getName()) {
                    $product->setSlug($this->slugger->slug($ProductTitle)->lower());
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}


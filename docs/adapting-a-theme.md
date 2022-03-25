# Adapting a theme to Twig

Many themes on ThemeForest and other places look great, but they're often simply just a set of HTML file.  
It can be challenging to move these to Twig, but here are some steps.

Use the ThemeAdapterController by enabling these route in routing/survos.yaml

The basic idea: Read the original .html file, and extract the body content.  Publish just the body to a temporary template in the _dynamic directory, which extends the base layout for the project.  Move the OLD header to the bottom, so you can still navigate.

Move the assets to the asset directory so the new system can use them, compile the js and css.  Images should also be *copied* to the public directory, so they're still accessible.



```php

    /**
     * @Route("/{oldRoute}", name="app_legacy_index", requirements={"oldRoute"=".+html"})
     */
    public function legacyIndex(Environment $twig, ParameterBagInterface $bag, Request $request, $oldRoute): Response
    {

        $root = $bag->get('kernel.project_dir');
        if (!file_exists($fn = $root . '/public/html/' . $oldRoute))
        {
            dd($fn);
        }
        $html = file_get_contents($fn);

        $template = $this->createTemplate($html);
        $source = $template->__toTwig();
        $twigPath = sprintf('_dynamic/%s.twig', $oldRoute);
        file_put_contents(sprintf('%s/templates/%s', $root, $twigPath), $source);

```

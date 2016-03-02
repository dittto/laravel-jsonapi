<?php namespace EchoIt\JsonApi;

use Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Pivot as Pivot;

/**
 * This class is used to extend models from, that will be exposed through
 * a JSON API.
 *
 * @author Ronni Egeriis Persson <ronni@egeriis.me>
 */
class Model extends \Eloquent
{
    /**
     * Let's guard these fields per default
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Has this model been changed inother ways than those
     * specified by the request
     *
     * Ref: http://jsonapi.org/format/#crud-updating-responses-200
     *
     * @var  boolean
     */
    protected $changed = false;

    /**
     * The resource type. If null, when the model is rendered,
     * the table name will be used
     *
     * @var  null|string
     */
    protected $resourceType = null;

    /**
     * Expose the resource relations links by default when viewing a
     * resource
     *
     * @var  array
     */
    protected $defaultExposedRelations = [];

    /**
     * mark this model as changed
     *
     * @return  void
     */
    public function markChanged($changed = true)
    {
        $this->changed = (bool) $changed;
    }

    /**
     * has this model been changed
     *
     * @return  void
     */
    public function isChanged()
    {
        return $this->changed;
    }

    /**
     * Get the resource type of the model
     *
     * @return  string
     */
    public function getResourceType()
    {
        // return the resource type if it is not null; table otherwize
        return ($this->resourceType ?: $this->getTable());
    }

    /**
     * Validate passed values
     *
     * @param  Array  $values  user passed values (request data)
     *
     * @return bool|Illuminate\Support\MessageBag  True on pass, MessageBag of errors on fail
     */
    public function validateArray(Array $values)
    {
        if (count($this->getValidationRules())) {
            $validator = Validator::make($values, $this->getValidationRules());

            if ($validator->fails()) {
                return $validator->errors();
            }
        }

        return True;
    }

    /**
     * Return model validation rules
     * Models should overload this to provide their validation rules
     *
     * @return Array validation rules
     */
    public function getValidationRules()
    {
        return [];
    }

    public function getPlural()
    {
        return \str_plural($this->resourceType);
    }

    /**
     * Convert the model instance to an array. This method overrides that of
     * Eloquent to prevent relations to be serialize into output array.
     *
     * @return array
     */
    public function toArray()
    {
        $relations = [];
        $arrayableRelations = [];

        // include any relations exposed by default
        $loadedRelations = $this->getRelations();
        foreach ($this->defaultExposedRelations as $relation) {

            if ( ! array_key_exists($relation, $loadedRelations))
            {
                $this->load($relation);
            }
        }

        // fetch the relations that can be represented as an array
        $arrayableRelations = array_merge($this->getArrayableRelations(), $arrayableRelations);

        // add the relations to the linked array
        foreach ($arrayableRelations as $relation => $value) {

            if (in_array($relation, $this->hidden)) {
                continue;
            }

            if ($value instanceof Pivot) {
                continue;
            }

            if ($value instanceof BaseModel) {
                $relations[$relation] = array('linkage' => array('id' => $value->getKey(), 'type' => $value->getResourceType()));
            } elseif ($value instanceof Collection) {

                $first = true;
                $items = ['linkage' => []];
                foreach ($value as $item) {

                    if ($first) {
                        // determine the plural name for the relation
                        $relation = $item->getPlural();
                    }

                    $items['linkage'][] = array('id' => $item->getKey(), 'type' => $item->getResourceType());
                    $first = false;
                }
                $relations[$relation] = $items;
            }

        }

        //add type parameter
        $model_attributes = $this->attributesToArray();
        unset($model_attributes[$this->primaryKey]);
        unset($model_attributes['type']);

        $attributes = [
            'id'         => $this->getKey(),
            'type'       => $this->getResourceType(),
            'attributes' => $model_attributes
        ];

        if (! count($relations)) {
            return $attributes;
        }

        return array_merge(
            $attributes,
            [ 'links' => $relations ]
        );
    }
}

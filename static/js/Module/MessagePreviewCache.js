export class MessagePreviewCache
{
    constructor()
    {
        this.cache = {};
    }

    exists(id)
    {
        return typeof this.cache[id] !== 'undefined';
    }

    get(id)
    {
        if (!this.exists(id)) {
            return false;
        }

        return this.cache[id];
    }

    set(id, data)
    {
        this.cache[id] = data;

        return true;
    }
}
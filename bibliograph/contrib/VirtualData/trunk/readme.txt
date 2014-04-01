VirtualData - Databinding for the virtual widgets
=================================================

This contribution provides models, controllers, and stores 
for TreeVirtual and Table to enable databinding.

Todo:

[X] Write a jsonrpc data store
[X] Rewrite qx.ui.treevirtual. SimpleTreeDataModel to use 
    qx.data.Array instead of native array.
[X] Demo for treevirtual
[ ] Rewrite qx.ui.table.model.* to use qx.data.Array instead
    of native array.
[ ] Demo for table
[ ] Controllers and marshalers
[ ] Databinding "across the wire": transport qx.data.Array events
    through a choice of transports (jsonrpc, cometd, ...)
[ ] Demo for server-backed databinding

    
import { Component, Injectable, OnDestroy, OnInit } from '@angular/core';
import { BehaviorSubject, merge, Observable, Subscription } from 'rxjs';
import { SiteService } from '../../../services/site.service';
import { SourceService } from '../../../services/source.service';
import { MatTreeNestedDataSource } from '@angular/material/tree';
import { FlatTreeControl, NestedTreeControl } from '@angular/cdk/tree';
import { map } from 'rxjs/operators';
import { CollectionViewer, SelectionChange } from '@angular/cdk/collections';

/** Flat node with expandable and level information */
export class DynamicFlatNode {
    constructor(public item: string, public level = 1, public expandable = false,
                public isLoading = false) {
    }
}

/**
 * Database for dynamic data. When expanding a node in the tree, the data source will need to fetch
 * the descendants data from the database.
 */
export class DynamicDatabase {

    /** Initial data from database */
    initialData(sourceService, site): Promise<DynamicFlatNode[]> {
        return sourceService.listFolder(site.siteKey).then(data => {
            return data.map(item => new DynamicFlatNode(item, 0, item.contentType === 'folder'));
        });
    }

    getChildren(node: any, sourceService, site): Promise<string[] | undefined> {
        return sourceService.listFolder(site.siteKey, node.leafName).then(data => {
            return data;
        });
    }

    isExpandable(node: any): boolean {
        return node.contentType === 'folder';
    }
}

/**
 * File database, it can build a tree structured Json object from string.
 * Each node in Json object represents a file or a directory. For a file, it has filename and type.
 * For a directory, it has filename and children (a list of files or directories).
 * The input will be a json object string, and the output is a list of `FileNode` with nested
 * structure.
 */
@Injectable()
export class DynamicDataSource {

    dataChange = new BehaviorSubject<DynamicFlatNode[]>([]);

    get data(): DynamicFlatNode[] {
        return this.dataChange.value;
    }

    set data(value: DynamicFlatNode[]) {
        this._treeControl.dataNodes = value;
        this.dataChange.next(value);
    }

    constructor(private _treeControl: FlatTreeControl<DynamicFlatNode>,
                private _database: DynamicDatabase,
                private sourceService: SourceService,
                private site: SiteService) {
    }

    connect(collectionViewer: CollectionViewer): Observable<DynamicFlatNode[]> {
        this._treeControl.expansionModel.changed.subscribe(change => {
            if ((change as SelectionChange<DynamicFlatNode>).added ||
                (change as SelectionChange<DynamicFlatNode>).removed) {
                this.handleTreeControl(change as SelectionChange<DynamicFlatNode>);
            }
        });

        return merge(collectionViewer.viewChange, this.dataChange).pipe(map(() => this.data));
    }

    /** Handle expand/collapse behaviors */
    handleTreeControl(change: SelectionChange<DynamicFlatNode>) {
        if (change.added) {
            change.added.forEach(node => this.toggleNode(node, true));
        }
        if (change.removed) {
            change.removed.slice().reverse().forEach(node => this.toggleNode(node, false));
        }
    }

    /**
     * Toggle the node, remove from display list
     */
    toggleNode(node: DynamicFlatNode, expand: boolean) {
        node.isLoading = true;
        this._database.getChildren(node.item, this.sourceService, this.site).then(children => {
            const index = this.data.indexOf(node);
            if (!children.length || index < 0) {
                node.isLoading = false;
                return;
            }
            if (expand) {
                const nodes = children.map(name =>
                    new DynamicFlatNode(name, node.level + 1, this._database.isExpandable(name)));
                this.data.splice(index + 1, 0, ...nodes);
            } else {
                let count = 0;
                for (let i = index + 1; i < this.data.length
                && this.data[i].level > node.level; i++, count++) {
                }
                this.data.splice(index + 1, count);
            }

            // notify the change
            this.dataChange.next(this.data);
            node.isLoading = false;
        });

    }
}

@Component({
    selector: 'app-source-files',
    templateUrl: './source-files.component.html',
    styleUrls: ['./source-files.component.sass']
})
export class SourceFilesComponent {

    public site: any;
    public initialLoad = true;
    public treeControl: FlatTreeControl<DynamicFlatNode>;
    public dataSource: DynamicDataSource;
    public getLevel = (node: DynamicFlatNode) => node.level;
    public isExpandable = (node: DynamicFlatNode) => node.expandable;
    public hasChild = (_: number, _nodeData: DynamicFlatNode) => _nodeData.expandable;

    constructor(public sourceService: SourceService,
                private siteService: SiteService) {

        this.site = this.siteService.activeSite.getValue();
        const database = new DynamicDatabase();

        this.treeControl = new FlatTreeControl<DynamicFlatNode>(this.getLevel, this.isExpandable);
        this.dataSource = new DynamicDataSource(this.treeControl, database, this.sourceService, this.site);

        database.initialData(this.sourceService, this.site).then(data => {
            this.dataSource.data = data;
            this.initialLoad = false;
        });
    }


}

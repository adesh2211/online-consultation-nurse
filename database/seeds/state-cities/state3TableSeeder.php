<?php

use Illuminate\Database\Seeder;

class state3TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Create Cities for the state of AR - Arkansas.
        //If the table 'cities' exists, insert the data to the table.
        if (DB::table('cities')->get()->count() >= 0) {
            DB::table('cities')->insert([
                ['name' => 'Almyra', 'state_id' => 3923],
                ['name' => 'Casscoe', 'state_id' => 3923],
                ['name' => 'Crocketts Bluff', 'state_id' => 3923],
                ['name' => 'De Witt', 'state_id' => 3923],
                ['name' => 'Ethel', 'state_id' => 3923],
                ['name' => 'Gillett', 'state_id' => 3923],
                ['name' => 'Humphrey', 'state_id' => 3923],
                ['name' => 'Saint Charles', 'state_id' => 3923],
                ['name' => 'Stuttgart', 'state_id' => 3923],
                ['name' => 'Tichnor', 'state_id' => 3923],
                ['name' => 'Crossett', 'state_id' => 3923],
                ['name' => 'Fountain Hill', 'state_id' => 3923],
                ['name' => 'Hamburg', 'state_id' => 3923],
                ['name' => 'Montrose', 'state_id' => 3923],
                ['name' => 'Parkdale', 'state_id' => 3923],
                ['name' => 'Portland', 'state_id' => 3923],
                ['name' => 'Wilmot', 'state_id' => 3923],
                ['name' => 'Gamaliel', 'state_id' => 3923],
                ['name' => 'Henderson', 'state_id' => 3923],
                ['name' => 'Big Flat', 'state_id' => 3923],
                ['name' => 'Clarkridge', 'state_id' => 3923],
                ['name' => 'Cotter', 'state_id' => 3923],
                ['name' => 'Gassville', 'state_id' => 3923],
                ['name' => 'Lakeview', 'state_id' => 3923],
                ['name' => 'Midway', 'state_id' => 3923],
                ['name' => 'Mountain Home', 'state_id' => 3923],
                ['name' => 'Norfork', 'state_id' => 3923],
                ['name' => 'Avoca', 'state_id' => 3923],
                ['name' => 'Bentonville', 'state_id' => 3923],
                ['name' => 'Bella Vista', 'state_id' => 3923],
                ['name' => 'Cave Springs', 'state_id' => 3923],
                ['name' => 'Centerton', 'state_id' => 3923],
                ['name' => 'Decatur', 'state_id' => 3923],
                ['name' => 'Garfield', 'state_id' => 3923],
                ['name' => 'Gateway', 'state_id' => 3923],
                ['name' => 'Gentry', 'state_id' => 3923],
                ['name' => 'Gravette', 'state_id' => 3923],
                ['name' => 'Hiwasse', 'state_id' => 3923],
                ['name' => 'Lowell', 'state_id' => 3923],
                ['name' => 'Maysville', 'state_id' => 3923],
                ['name' => 'Pea Ridge', 'state_id' => 3923],
                ['name' => 'Rogers', 'state_id' => 3923],
                ['name' => 'Siloam Springs', 'state_id' => 3923],
                ['name' => 'Sulphur Springs', 'state_id' => 3923],
                ['name' => 'Little Rock', 'state_id' => 3923],
                ['name' => 'Harrison', 'state_id' => 3923],
                ['name' => 'Alpena', 'state_id' => 3923],
                ['name' => 'Bergman', 'state_id' => 3923],
                ['name' => 'Diamond City', 'state_id' => 3923],
                ['name' => 'Everton', 'state_id' => 3923],
                ['name' => 'Lead Hill', 'state_id' => 3923],
                ['name' => 'Omaha', 'state_id' => 3923],
                ['name' => 'Valley Springs', 'state_id' => 3923],
                ['name' => 'Banks', 'state_id' => 3923],
                ['name' => 'Hermitage', 'state_id' => 3923],
                ['name' => 'Jersey', 'state_id' => 3923],
                ['name' => 'Warren', 'state_id' => 3923],
                ['name' => 'Hampton', 'state_id' => 3923],
                ['name' => 'Harrell', 'state_id' => 3923],
                ['name' => 'Thornton', 'state_id' => 3923],
                ['name' => 'Beaver', 'state_id' => 3923],
                ['name' => 'Berryville', 'state_id' => 3923],
                ['name' => 'Eureka Springs', 'state_id' => 3923],
                ['name' => 'Green Forest', 'state_id' => 3923],
                ['name' => 'Oak Grove', 'state_id' => 3923],
                ['name' => 'Dermott', 'state_id' => 3923],
                ['name' => 'Eudora', 'state_id' => 3923],
                ['name' => 'Lake Village', 'state_id' => 3923],
                ['name' => 'Beirne', 'state_id' => 3923],
                ['name' => 'Curtis', 'state_id' => 3923],
                ['name' => 'Gurdon', 'state_id' => 3923],
                ['name' => 'Whelen Springs', 'state_id' => 3923],
                ['name' => 'Alpine', 'state_id' => 3923],
                ['name' => 'Amity', 'state_id' => 3923],
                ['name' => 'Arkadelphia', 'state_id' => 3923],
                ['name' => 'Okolona', 'state_id' => 3923],
                ['name' => 'Corning', 'state_id' => 3923],
                ['name' => 'Datto', 'state_id' => 3923],
                ['name' => 'Greenway', 'state_id' => 3923],
                ['name' => 'Knobel', 'state_id' => 3923],
                ['name' => 'Mc Dougal', 'state_id' => 3923],
                ['name' => 'Peach Orchard', 'state_id' => 3923],
                ['name' => 'Piggott', 'state_id' => 3923],
                ['name' => 'Pollard', 'state_id' => 3923],
                ['name' => 'Rector', 'state_id' => 3923],
                ['name' => 'Saint Francis', 'state_id' => 3923],
                ['name' => 'Success', 'state_id' => 3923],
                ['name' => 'Edgemont', 'state_id' => 3923],
                ['name' => 'Higden', 'state_id' => 3923],
                ['name' => 'Prim', 'state_id' => 3923],
                ['name' => 'Quitman', 'state_id' => 3923],
                ['name' => 'Wilburn', 'state_id' => 3923],
                ['name' => 'Concord', 'state_id' => 3923],
                ['name' => 'Drasco', 'state_id' => 3923],
                ['name' => 'Heber Springs', 'state_id' => 3923],
                ['name' => 'Ida', 'state_id' => 3923],
                ['name' => 'Tumbling Shoals', 'state_id' => 3923],
                ['name' => 'Kingsland', 'state_id' => 3923],
                ['name' => 'New Edinburg', 'state_id' => 3923],
                ['name' => 'Rison', 'state_id' => 3923],
                ['name' => 'Emerson', 'state_id' => 3923],
                ['name' => 'Mc Neil', 'state_id' => 3923],
                ['name' => 'Magnolia', 'state_id' => 3923],
                ['name' => 'Waldo', 'state_id' => 3923],
                ['name' => 'Taylor', 'state_id' => 3923],
                ['name' => 'Center Ridge', 'state_id' => 3923],
                ['name' => 'Cleveland', 'state_id' => 3923],
                ['name' => 'Hattieville', 'state_id' => 3923],
                ['name' => 'Jerusalem', 'state_id' => 3923],
                ['name' => 'Menifee', 'state_id' => 3923],
                ['name' => 'Morrilton', 'state_id' => 3923],
                ['name' => 'Plumerville', 'state_id' => 3923],
                ['name' => 'Solgohachia', 'state_id' => 3923],
                ['name' => 'Springfield', 'state_id' => 3923],
                ['name' => 'Jonesboro', 'state_id' => 3923],
                ['name' => 'Bay', 'state_id' => 3923],
                ['name' => 'Black Oak', 'state_id' => 3923],
                ['name' => 'Bono', 'state_id' => 3923],
                ['name' => 'Brookland', 'state_id' => 3923],
                ['name' => 'Caraway', 'state_id' => 3923],
                ['name' => 'Cash', 'state_id' => 3923],
                ['name' => 'Egypt', 'state_id' => 3923],
                ['name' => 'Lake City', 'state_id' => 3923],
                ['name' => 'Monette', 'state_id' => 3923],
                ['name' => 'State University', 'state_id' => 3923],
                ['name' => 'Alma', 'state_id' => 3923],
                ['name' => 'Cedarville', 'state_id' => 3923],
                ['name' => 'Chester', 'state_id' => 3923],
                ['name' => 'Dyer', 'state_id' => 3923],
                ['name' => 'Mountainburg', 'state_id' => 3923],
                ['name' => 'Mulberry', 'state_id' => 3923],
                ['name' => 'Natural Dam', 'state_id' => 3923],
                ['name' => 'Rudy', 'state_id' => 3923],
                ['name' => 'Uniontown', 'state_id' => 3923],
                ['name' => 'Van Buren', 'state_id' => 3923],
                ['name' => 'West Memphis', 'state_id' => 3923],
                ['name' => 'Clarkedale', 'state_id' => 3923],
                ['name' => 'Crawfordsville', 'state_id' => 3923],
                ['name' => 'Earle', 'state_id' => 3923],
                ['name' => 'Edmondson', 'state_id' => 3923],
                ['name' => 'Gilmore', 'state_id' => 3923],
                ['name' => 'Marion', 'state_id' => 3923],
                ['name' => 'Proctor', 'state_id' => 3923],
                ['name' => 'Turrell', 'state_id' => 3923],
                ['name' => 'Cherry Valley', 'state_id' => 3923],
                ['name' => 'Hickory Ridge', 'state_id' => 3923],
                ['name' => 'Parkin', 'state_id' => 3923],
                ['name' => 'Vanndale', 'state_id' => 3923],
                ['name' => 'Wynne', 'state_id' => 3923],
                ['name' => 'Carthage', 'state_id' => 3923],
                ['name' => 'Fordyce', 'state_id' => 3923],
                ['name' => 'Ivan', 'state_id' => 3923],
                ['name' => 'Sparkman', 'state_id' => 3923],
                ['name' => 'Arkansas City', 'state_id' => 3923],
                ['name' => 'Dumas', 'state_id' => 3923],
                ['name' => 'Mc Gehee', 'state_id' => 3923],
                ['name' => 'Pickens', 'state_id' => 3923],
                ['name' => 'Rohwer', 'state_id' => 3923],
                ['name' => 'Tillar', 'state_id' => 3923],
                ['name' => 'Watson', 'state_id' => 3923],
                ['name' => 'Snow Lake', 'state_id' => 3923],
                ['name' => 'Monticello', 'state_id' => 3923],
                ['name' => 'Wilmar', 'state_id' => 3923],
                ['name' => 'Winchester', 'state_id' => 3923],
                ['name' => 'Conway', 'state_id' => 3923],
                ['name' => 'Damascus', 'state_id' => 3923],
                ['name' => 'Enola', 'state_id' => 3923],
                ['name' => 'Greenbrier', 'state_id' => 3923],
                ['name' => 'Guy', 'state_id' => 3923],
                ['name' => 'Mayflower', 'state_id' => 3923],
                ['name' => 'Mount Vernon', 'state_id' => 3923],
                ['name' => 'Vilonia', 'state_id' => 3923],
                ['name' => 'Wooster', 'state_id' => 3923],
                ['name' => 'Alix', 'state_id' => 3923],
                ['name' => 'Altus', 'state_id' => 3923],
                ['name' => 'Branch', 'state_id' => 3923],
                ['name' => 'Cecil', 'state_id' => 3923],
                ['name' => 'Charleston', 'state_id' => 3923],
                ['name' => 'Ozark', 'state_id' => 3923],
                ['name' => 'Bexar', 'state_id' => 3923],
                ['name' => 'Camp', 'state_id' => 3923],
                ['name' => 'Elizabeth', 'state_id' => 3923],
                ['name' => 'Gepp', 'state_id' => 3923],
                ['name' => 'Glencoe', 'state_id' => 3923],
                ['name' => 'Mammoth Spring', 'state_id' => 3923],
                ['name' => 'Salem', 'state_id' => 3923],
                ['name' => 'Sturkie', 'state_id' => 3923],
                ['name' => 'Viola', 'state_id' => 3923],
                ['name' => 'Hot Springs National Park', 'state_id' => 3923],
                ['name' => 'Hot Springs Village', 'state_id' => 3923],
                ['name' => 'Jessieville', 'state_id' => 3923],
                ['name' => 'Mountain Pine', 'state_id' => 3923],
                ['name' => 'Pearcy', 'state_id' => 3923],
                ['name' => 'Royal', 'state_id' => 3923],
                ['name' => 'Lonsdale', 'state_id' => 3923],
                ['name' => 'Grapevine', 'state_id' => 3923],
                ['name' => 'Leola', 'state_id' => 3923],
                ['name' => 'Poyen', 'state_id' => 3923],
                ['name' => 'Prattsville', 'state_id' => 3923],
                ['name' => 'Sheridan', 'state_id' => 3923],
                ['name' => 'Beech Grove', 'state_id' => 3923],
                ['name' => 'Delaplaine', 'state_id' => 3923],
                ['name' => 'Lafe', 'state_id' => 3923],
                ['name' => 'Marmaduke', 'state_id' => 3923],
                ['name' => 'Paragould', 'state_id' => 3923],
                ['name' => 'Walcott', 'state_id' => 3923],
                ['name' => 'Hope', 'state_id' => 3923],
                ['name' => 'Blevins', 'state_id' => 3923],
                ['name' => 'Columbus', 'state_id' => 3923],
                ['name' => 'Fulton', 'state_id' => 3923],
                ['name' => 'Mc Caskill', 'state_id' => 3923],
                ['name' => 'Ozan', 'state_id' => 3923],
                ['name' => 'Washington', 'state_id' => 3923],
                ['name' => 'Bismarck', 'state_id' => 3923],
                ['name' => 'Bonnerdale', 'state_id' => 3923],
                ['name' => 'Donaldson', 'state_id' => 3923],
                ['name' => 'Friendship', 'state_id' => 3923],
                ['name' => 'Benton', 'state_id' => 3923],
                ['name' => 'Malvern', 'state_id' => 3923],
                ['name' => 'Jones Mill', 'state_id' => 3923],
                ['name' => 'Dierks', 'state_id' => 3923],
                ['name' => 'Mineral Springs', 'state_id' => 3923],
                ['name' => 'Nashville', 'state_id' => 3923],
                ['name' => 'Saratoga', 'state_id' => 3923],
                ['name' => 'Umpire', 'state_id' => 3923],
                ['name' => 'Thida', 'state_id' => 3923],
                ['name' => 'Batesville', 'state_id' => 3923],
                ['name' => 'Charlotte', 'state_id' => 3923],
                ['name' => 'Cord', 'state_id' => 3923],
                ['name' => 'Cushman', 'state_id' => 3923],
                ['name' => 'Desha', 'state_id' => 3923],
                ['name' => 'Floral', 'state_id' => 3923],
                ['name' => 'Locust Grove', 'state_id' => 3923],
                ['name' => 'Magness', 'state_id' => 3923],
                ['name' => 'Newark', 'state_id' => 3923],
                ['name' => 'Oil Trough', 'state_id' => 3923],
                ['name' => 'Pleasant Plains', 'state_id' => 3923],
                ['name' => 'Rosie', 'state_id' => 3923],
                ['name' => 'Salado', 'state_id' => 3923],
                ['name' => 'Sulphur Rock', 'state_id' => 3923],
                ['name' => 'Horseshoe Bend', 'state_id' => 3923],
                ['name' => 'Brockwell', 'state_id' => 3923],
                ['name' => 'Calico Rock', 'state_id' => 3923],
                ['name' => 'Dolph', 'state_id' => 3923],
                ['name' => 'Franklin', 'state_id' => 3923],
                ['name' => 'Guion', 'state_id' => 3923],
                ['name' => 'Melbourne', 'state_id' => 3923],
                ['name' => 'Mount Pleasant', 'state_id' => 3923],
                ['name' => 'Oxford', 'state_id' => 3923],
                ['name' => 'Pineville', 'state_id' => 3923],
                ['name' => 'Sage', 'state_id' => 3923],
                ['name' => 'Violet Hill', 'state_id' => 3923],
                ['name' => 'Wideman', 'state_id' => 3923],
                ['name' => 'Wiseman', 'state_id' => 3923],
                ['name' => 'Amagon', 'state_id' => 3923],
                ['name' => 'Beedeville', 'state_id' => 3923],
                ['name' => 'Diaz', 'state_id' => 3923],
                ['name' => 'Jacksonport', 'state_id' => 3923],
                ['name' => 'Newport', 'state_id' => 3923],
                ['name' => 'Tupelo', 'state_id' => 3923],
                ['name' => 'Grubbs', 'state_id' => 3923],
                ['name' => 'Swifton', 'state_id' => 3923],
                ['name' => 'Tuckerman', 'state_id' => 3923],
                ['name' => 'Pine Bluff', 'state_id' => 3923],
                ['name' => 'White Hall', 'state_id' => 3923],
                ['name' => 'Moscow', 'state_id' => 3923],
                ['name' => 'Altheimer', 'state_id' => 3923],
                ['name' => 'Jefferson', 'state_id' => 3923],
                ['name' => 'Redfield', 'state_id' => 3923],
                ['name' => 'Reydell', 'state_id' => 3923],
                ['name' => 'Sherrill', 'state_id' => 3923],
                ['name' => 'Tucker', 'state_id' => 3923],
                ['name' => 'Wabbaseka', 'state_id' => 3923],
                ['name' => 'Wright', 'state_id' => 3923],
                ['name' => 'Clarksville', 'state_id' => 3923],
                ['name' => 'Coal Hill', 'state_id' => 3923],
                ['name' => 'Hagarville', 'state_id' => 3923],
                ['name' => 'Hartman', 'state_id' => 3923],
                ['name' => 'Knoxville', 'state_id' => 3923],
                ['name' => 'Lamar', 'state_id' => 3923],
                ['name' => 'Oark', 'state_id' => 3923],
                ['name' => 'Ozone', 'state_id' => 3923],
                ['name' => 'Bradley', 'state_id' => 3923],
                ['name' => 'Buckner', 'state_id' => 3923],
                ['name' => 'Garland City', 'state_id' => 3923],
                ['name' => 'Lewisville', 'state_id' => 3923],
                ['name' => 'Stamps', 'state_id' => 3923],
                ['name' => 'Alicia', 'state_id' => 3923],
                ['name' => 'Black Rock', 'state_id' => 3923],
                ['name' => 'Hoxie', 'state_id' => 3923],
                ['name' => 'Imboden', 'state_id' => 3923],
                ['name' => 'Lynn', 'state_id' => 3923],
                ['name' => 'Minturn', 'state_id' => 3923],
                ['name' => 'Portia', 'state_id' => 3923],
                ['name' => 'Powhatan', 'state_id' => 3923],
                ['name' => 'Ravenden', 'state_id' => 3923],
                ['name' => 'Sedgwick', 'state_id' => 3923],
                ['name' => 'Smithville', 'state_id' => 3923],
                ['name' => 'Strawberry', 'state_id' => 3923],
                ['name' => 'Walnut Ridge', 'state_id' => 3923],
                ['name' => 'Saffell', 'state_id' => 3923],
                ['name' => 'Aubrey', 'state_id' => 3923],
                ['name' => 'Brickeys', 'state_id' => 3923],
                ['name' => 'Haynes', 'state_id' => 3923],
                ['name' => 'La Grange', 'state_id' => 3923],
                ['name' => 'Marianna', 'state_id' => 3923],
                ['name' => 'Moro', 'state_id' => 3923],
                ['name' => 'Gould', 'state_id' => 3923],
                ['name' => 'Grady', 'state_id' => 3923],
                ['name' => 'Star City', 'state_id' => 3923],
                ['name' => 'Yorktown', 'state_id' => 3923],
                ['name' => 'Alleene', 'state_id' => 3923],
                ['name' => 'Ashdown', 'state_id' => 3923],
                ['name' => 'Foreman', 'state_id' => 3923],
                ['name' => 'Ogden', 'state_id' => 3923],
                ['name' => 'Wilton', 'state_id' => 3923],
                ['name' => 'Winthrop', 'state_id' => 3923],
                ['name' => 'Blue Mountain', 'state_id' => 3923],
                ['name' => 'Delaware', 'state_id' => 3923],
                ['name' => 'New Blaine', 'state_id' => 3923],
                ['name' => 'Paris', 'state_id' => 3923],
                ['name' => 'Scranton', 'state_id' => 3923],
                ['name' => 'Subiaco', 'state_id' => 3923],
                ['name' => 'Booneville', 'state_id' => 3923],
                ['name' => 'Magazine', 'state_id' => 3923],
                ['name' => 'Ratcliff', 'state_id' => 3923],
                ['name' => 'Austin', 'state_id' => 3923],
                ['name' => 'Cabot', 'state_id' => 3923],
                ['name' => 'Carlisle', 'state_id' => 3923],
                ['name' => 'Coy', 'state_id' => 3923],
                ['name' => 'England', 'state_id' => 3923],
                ['name' => 'Humnoke', 'state_id' => 3923],
                ['name' => 'Keo', 'state_id' => 3923],
                ['name' => 'Lonoke', 'state_id' => 3923],
                ['name' => 'Ward', 'state_id' => 3923],
                ['name' => 'Combs', 'state_id' => 3923],
                ['name' => 'Hindsville', 'state_id' => 3923],
                ['name' => 'Huntsville', 'state_id' => 3923],
                ['name' => 'Kingston', 'state_id' => 3923],
                ['name' => 'Pettigrew', 'state_id' => 3923],
                ['name' => 'Saint Paul', 'state_id' => 3923],
                ['name' => 'Wesley', 'state_id' => 3923],
                ['name' => 'Witter', 'state_id' => 3923],
                ['name' => 'Bull Shoals', 'state_id' => 3923],
                ['name' => 'Flippin', 'state_id' => 3923],
                ['name' => 'Oakland', 'state_id' => 3923],
                ['name' => 'Peel', 'state_id' => 3923],
                ['name' => 'Pyatt', 'state_id' => 3923],
                ['name' => 'Summit', 'state_id' => 3923],
                ['name' => 'Yellville', 'state_id' => 3923],
                ['name' => 'Doddridge', 'state_id' => 3923],
                ['name' => 'Fouke', 'state_id' => 3923],
                ['name' => 'Genoa', 'state_id' => 3923],
                ['name' => 'Texarkana', 'state_id' => 3923],
                ['name' => 'Armorel', 'state_id' => 3923],
                ['name' => 'Bassett', 'state_id' => 3923],
                ['name' => 'Blytheville', 'state_id' => 3923],
                ['name' => 'Gosnell', 'state_id' => 3923],
                ['name' => 'Burdette', 'state_id' => 3923],
                ['name' => 'Driver', 'state_id' => 3923],
                ['name' => 'Dyess', 'state_id' => 3923],
                ['name' => 'Frenchmans Bayou', 'state_id' => 3923],
                ['name' => 'Joiner', 'state_id' => 3923],
                ['name' => 'Keiser', 'state_id' => 3923],
                ['name' => 'Luxora', 'state_id' => 3923],
                ['name' => 'Osceola', 'state_id' => 3923],
                ['name' => 'West Ridge', 'state_id' => 3923],
                ['name' => 'Wilson', 'state_id' => 3923],
                ['name' => 'Dell', 'state_id' => 3923],
                ['name' => 'Etowah', 'state_id' => 3923],
                ['name' => 'Leachville', 'state_id' => 3923],
                ['name' => 'Manila', 'state_id' => 3923],
                ['name' => 'Brinkley', 'state_id' => 3923],
                ['name' => 'Clarendon', 'state_id' => 3923],
                ['name' => 'Holly Grove', 'state_id' => 3923],
                ['name' => 'Monroe', 'state_id' => 3923],
                ['name' => 'Roe', 'state_id' => 3923],
                ['name' => 'Caddo Gap', 'state_id' => 3923],
                ['name' => 'Mount Ida', 'state_id' => 3923],
                ['name' => 'Norman', 'state_id' => 3923],
                ['name' => 'Oden', 'state_id' => 3923],
                ['name' => 'Pencil Bluff', 'state_id' => 3923],
                ['name' => 'Sims', 'state_id' => 3923],
                ['name' => 'Story', 'state_id' => 3923],
                ['name' => 'Bluff City', 'state_id' => 3923],
                ['name' => 'Cale', 'state_id' => 3923],
                ['name' => 'Emmet', 'state_id' => 3923],
                ['name' => 'Prescott', 'state_id' => 3923],
                ['name' => 'Rosston', 'state_id' => 3923],
                ['name' => 'Willisville', 'state_id' => 3923],
                ['name' => 'Compton', 'state_id' => 3923],
                ['name' => 'Deer', 'state_id' => 3923],
                ['name' => 'Hasty', 'state_id' => 3923],
                ['name' => 'Jasper', 'state_id' => 3923],
                ['name' => 'Marble Falls', 'state_id' => 3923],
                ['name' => 'Mount Judea', 'state_id' => 3923],
                ['name' => 'Parthenon', 'state_id' => 3923],
                ['name' => 'Ponca', 'state_id' => 3923],
                ['name' => 'Vendor', 'state_id' => 3923],
                ['name' => 'Western Grove', 'state_id' => 3923],
                ['name' => 'Camden', 'state_id' => 3923],
                ['name' => 'Bearden', 'state_id' => 3923],
                ['name' => 'Chidester', 'state_id' => 3923],
                ['name' => 'Louann', 'state_id' => 3923],
                ['name' => 'Stephens', 'state_id' => 3923],
                ['name' => 'Adona', 'state_id' => 3923],
                ['name' => 'Bigelow', 'state_id' => 3923],
                ['name' => 'Casa', 'state_id' => 3923],
                ['name' => 'Houston', 'state_id' => 3923],
                ['name' => 'Perry', 'state_id' => 3923],
                ['name' => 'Perryville', 'state_id' => 3923],
                ['name' => 'Barton', 'state_id' => 3923],
                ['name' => 'Crumrod', 'state_id' => 3923],
                ['name' => 'Elaine', 'state_id' => 3923],
                ['name' => 'Helena', 'state_id' => 3923],
                ['name' => 'Lambrook', 'state_id' => 3923],
                ['name' => 'Lexa', 'state_id' => 3923],
                ['name' => 'Marvell', 'state_id' => 3923],
                ['name' => 'Mellwood', 'state_id' => 3923],
                ['name' => 'Oneida', 'state_id' => 3923],
                ['name' => 'Poplar Grove', 'state_id' => 3923],
                ['name' => 'Turner', 'state_id' => 3923],
                ['name' => 'Wabash', 'state_id' => 3923],
                ['name' => 'West Helena', 'state_id' => 3923],
                ['name' => 'Antoine', 'state_id' => 3923],
                ['name' => 'Delight', 'state_id' => 3923],
                ['name' => 'Glenwood', 'state_id' => 3923],
                ['name' => 'Kirby', 'state_id' => 3923],
                ['name' => 'Langley', 'state_id' => 3923],
                ['name' => 'Murfreesboro', 'state_id' => 3923],
                ['name' => 'Newhope', 'state_id' => 3923],
                ['name' => 'Lepanto', 'state_id' => 3923],
                ['name' => 'Marked Tree', 'state_id' => 3923],
                ['name' => 'Rivervale', 'state_id' => 3923],
                ['name' => 'Tyronza', 'state_id' => 3923],
                ['name' => 'Fisher', 'state_id' => 3923],
                ['name' => 'Harrisburg', 'state_id' => 3923],
                ['name' => 'Trumann', 'state_id' => 3923],
                ['name' => 'Waldenburg', 'state_id' => 3923],
                ['name' => 'Weiner', 'state_id' => 3923],
                ['name' => 'Board Camp', 'state_id' => 3923],
                ['name' => 'Cove', 'state_id' => 3923],
                ['name' => 'Grannis', 'state_id' => 3923],
                ['name' => 'Hatfield', 'state_id' => 3923],
                ['name' => 'Mena', 'state_id' => 3923],
                ['name' => 'Vandervoort', 'state_id' => 3923],
                ['name' => 'Wickes', 'state_id' => 3923],
                ['name' => 'Tilly', 'state_id' => 3923],
                ['name' => 'Russellville', 'state_id' => 3923],
                ['name' => 'Atkins', 'state_id' => 3923],
                ['name' => 'Dover', 'state_id' => 3923],
                ['name' => 'Hector', 'state_id' => 3923],
                ['name' => 'London', 'state_id' => 3923],
                ['name' => 'Pelsor', 'state_id' => 3923],
                ['name' => 'Pottsville', 'state_id' => 3923],
                ['name' => 'Biscoe', 'state_id' => 3923],
                ['name' => 'Des Arc', 'state_id' => 3923],
                ['name' => 'De Valls Bluff', 'state_id' => 3923],
                ['name' => 'Hazen', 'state_id' => 3923],
                ['name' => 'Hickory Plains', 'state_id' => 3923],
                ['name' => 'Ulm', 'state_id' => 3923],
                ['name' => 'Alexander', 'state_id' => 3923],
                ['name' => 'College Station', 'state_id' => 3923],
                ['name' => 'Hensley', 'state_id' => 3923],
                ['name' => 'Jacksonville', 'state_id' => 3923],
                ['name' => 'Little Rock Air Force Base', 'state_id' => 3923],
                ['name' => 'Mabelvale', 'state_id' => 3923],
                ['name' => 'Maumelle', 'state_id' => 3923],
                ['name' => 'North Little Rock', 'state_id' => 3923],
                ['name' => 'Sherwood', 'state_id' => 3923],
                ['name' => 'Roland', 'state_id' => 3923],
                ['name' => 'Scott', 'state_id' => 3923],
                ['name' => 'Sweet Home', 'state_id' => 3923],
                ['name' => 'Woodson', 'state_id' => 3923],
                ['name' => 'Wrightsville', 'state_id' => 3923],
                ['name' => 'Biggers', 'state_id' => 3923],
                ['name' => 'Maynard', 'state_id' => 3923],
                ['name' => 'O Kean', 'state_id' => 3923],
                ['name' => 'Pocahontas', 'state_id' => 3923],
                ['name' => 'Ravenden Springs', 'state_id' => 3923],
                ['name' => 'Reyno', 'state_id' => 3923],
                ['name' => 'Warm Springs', 'state_id' => 3923],
                ['name' => 'Caldwell', 'state_id' => 3923],
                ['name' => 'Colt', 'state_id' => 3923],
                ['name' => 'Forrest City', 'state_id' => 3923],
                ['name' => 'Goodwin', 'state_id' => 3923],
                ['name' => 'Heth', 'state_id' => 3923],
                ['name' => 'Hughes', 'state_id' => 3923],
                ['name' => 'Madison', 'state_id' => 3923],
                ['name' => 'Palestine', 'state_id' => 3923],
                ['name' => 'Wheatley', 'state_id' => 3923],
                ['name' => 'Widener', 'state_id' => 3923],
                ['name' => 'Bauxite', 'state_id' => 3923],
                ['name' => 'Bryant', 'state_id' => 3923],
                ['name' => 'Paron', 'state_id' => 3923],
                ['name' => 'Traskwood', 'state_id' => 3923],
                ['name' => 'Harvey', 'state_id' => 3923],
                ['name' => 'Boles', 'state_id' => 3923],
                ['name' => 'Mansfield', 'state_id' => 3923],
                ['name' => 'Parks', 'state_id' => 3923],
                ['name' => 'Waldron', 'state_id' => 3923],
                ['name' => 'Gilbert', 'state_id' => 3923],
                ['name' => 'Harriet', 'state_id' => 3923],
                ['name' => 'Leslie', 'state_id' => 3923],
                ['name' => 'Marshall', 'state_id' => 3923],
                ['name' => 'Pindall', 'state_id' => 3923],
                ['name' => 'Saint Joe', 'state_id' => 3923],
                ['name' => 'Witts Springs', 'state_id' => 3923],
                ['name' => 'Fort Smith', 'state_id' => 3923],
                ['name' => 'Barling', 'state_id' => 3923],
                ['name' => 'Greenwood', 'state_id' => 3923],
                ['name' => 'Hackett', 'state_id' => 3923],
                ['name' => 'Hartford', 'state_id' => 3923],
                ['name' => 'Huntington', 'state_id' => 3923],
                ['name' => 'Lavaca', 'state_id' => 3923],
                ['name' => 'Midland', 'state_id' => 3923],
                ['name' => 'Ben Lomond', 'state_id' => 3923],
                ['name' => 'De Queen', 'state_id' => 3923],
                ['name' => 'Gillham', 'state_id' => 3923],
                ['name' => 'Horatio', 'state_id' => 3923],
                ['name' => 'Lockesburg', 'state_id' => 3923],
                ['name' => 'Williford', 'state_id' => 3923],
                ['name' => 'Ash Flat', 'state_id' => 3923],
                ['name' => 'Cave City', 'state_id' => 3923],
                ['name' => 'Cherokee Village', 'state_id' => 3923],
                ['name' => 'Evening Shade', 'state_id' => 3923],
                ['name' => 'Hardy', 'state_id' => 3923],
                ['name' => 'Poughkeepsie', 'state_id' => 3923],
                ['name' => 'Sidney', 'state_id' => 3923],
                ['name' => 'Fox', 'state_id' => 3923],
                ['name' => 'Fifty Six', 'state_id' => 3923],
                ['name' => 'Marcella', 'state_id' => 3923],
                ['name' => 'Mountain View', 'state_id' => 3923],
                ['name' => 'Pleasant Grove', 'state_id' => 3923],
                ['name' => 'Timbo', 'state_id' => 3923],
                ['name' => 'Onia', 'state_id' => 3923],
                ['name' => 'Calion', 'state_id' => 3923],
                ['name' => 'El Dorado', 'state_id' => 3923],
                ['name' => 'Huttig', 'state_id' => 3923],
                ['name' => 'Junction City', 'state_id' => 3923],
                ['name' => 'Lawson', 'state_id' => 3923],
                ['name' => 'Mount Holly', 'state_id' => 3923],
                ['name' => 'Norphlet', 'state_id' => 3923],
                ['name' => 'Smackover', 'state_id' => 3923],
                ['name' => 'Strong', 'state_id' => 3923],
                ['name' => 'Bee Branch', 'state_id' => 3923],
                ['name' => 'Choctaw', 'state_id' => 3923],
                ['name' => 'Clinton', 'state_id' => 3923],
                ['name' => 'Fairfield Bay', 'state_id' => 3923],
                ['name' => 'Scotland', 'state_id' => 3923],
                ['name' => 'Shirley', 'state_id' => 3923],
                ['name' => 'Dennard', 'state_id' => 3923],
                ['name' => 'Fayetteville', 'state_id' => 3923],
                ['name' => 'Canehill', 'state_id' => 3923],
                ['name' => 'Elkins', 'state_id' => 3923],
                ['name' => 'Elm Springs', 'state_id' => 3923],
                ['name' => 'Evansville', 'state_id' => 3923],
                ['name' => 'Farmington', 'state_id' => 3923],
                ['name' => 'Goshen', 'state_id' => 3923],
                ['name' => 'Greenland', 'state_id' => 3923],
                ['name' => 'Johnson', 'state_id' => 3923],
                ['name' => 'Lincoln', 'state_id' => 3923],
                ['name' => 'Morrow', 'state_id' => 3923],
                ['name' => 'Prairie Grove', 'state_id' => 3923],
                ['name' => 'Springdale', 'state_id' => 3923],
                ['name' => 'Summers', 'state_id' => 3923],
                ['name' => 'Tontitown', 'state_id' => 3923],
                ['name' => 'West Fork', 'state_id' => 3923],
                ['name' => 'Winslow', 'state_id' => 3923],
                ['name' => 'Bald Knob', 'state_id' => 3923],
                ['name' => 'Beebe', 'state_id' => 3923],
                ['name' => 'Bradford', 'state_id' => 3923],
                ['name' => 'El Paso', 'state_id' => 3923],
                ['name' => 'Garner', 'state_id' => 3923],
                ['name' => 'Griffithville', 'state_id' => 3923],
                ['name' => 'Higginson', 'state_id' => 3923],
                ['name' => 'Judsonia', 'state_id' => 3923],
                ['name' => 'Kensett', 'state_id' => 3923],
                ['name' => 'Letona', 'state_id' => 3923],
                ['name' => 'Mc Rae', 'state_id' => 3923],
                ['name' => 'Pangburn', 'state_id' => 3923],
                ['name' => 'Romance', 'state_id' => 3923],
                ['name' => 'Rose Bud', 'state_id' => 3923],
                ['name' => 'Russell', 'state_id' => 3923],
                ['name' => 'Searcy', 'state_id' => 3923],
                ['name' => 'West Point', 'state_id' => 3923],
                ['name' => 'Augusta', 'state_id' => 3923],
                ['name' => 'Cotton Plant', 'state_id' => 3923],
                ['name' => 'Gregory', 'state_id' => 3923],
                ['name' => 'Hunter', 'state_id' => 3923],
                ['name' => 'Mc Crory', 'state_id' => 3923],
                ['name' => 'Patterson', 'state_id' => 3923],
                ['name' => 'Belleville', 'state_id' => 3923],
                ['name' => 'Bluffton', 'state_id' => 3923],
                ['name' => 'Briggsville', 'state_id' => 3923],
                ['name' => 'Centerville', 'state_id' => 3923],
                ['name' => 'Danville', 'state_id' => 3923],
                ['name' => 'Dardanelle', 'state_id' => 3923],
                ['name' => 'Gravelly', 'state_id' => 3923],
                ['name' => 'Havana', 'state_id' => 3923],
                ['name' => 'Ola', 'state_id' => 3923],
                ['name' => 'Plainview', 'state_id' => 3923],
                ['name' => 'Rover', 'state_id' => 3923]
            ]);
        }
    }
}
